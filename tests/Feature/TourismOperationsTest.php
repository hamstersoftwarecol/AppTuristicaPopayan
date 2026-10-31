<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Post;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\TravelInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TourismOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function account(Role $role): User
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function provider(?User $owner = null, string $status = 'active'): Provider
    {
        return Provider::create(['name' => 'Operador local', 'contact_email' => 'operador@example.com', 'status' => $status, 'owner_id' => $owner?->id, 'notes' => 'Nota interna que no debe exponerse.']);
    }

    /** @return array<string, mixed> */
    private function serviceData(Provider $provider, string $status = 'draft'): array
    {
        return ['provider_id' => $provider->id, 'title' => 'Experiencia en Popayán', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Una descripción suficientemente larga del servicio turístico.', 'conditions' => 'Sujeto a disponibilidad.', 'reference_price' => 80000, 'currency' => 'COP', 'status' => $status];
    }

    public function test_admin_can_create_and_update_providers_with_an_audit_trail(): void
    {
        $admin = $this->account(Role::Admin);
        $owner = $this->account(Role::Supplier);
        $data = ['name' => 'Operador nuevo', 'contact_email' => 'nuevo@example.com', 'status' => 'active', 'owner_id' => $owner->id];
        $this->actingAs($admin)->post('/gestion/proveedores', $data)->assertSessionHasNoErrors()->assertRedirect();
        $provider = Provider::firstOrFail();
        $this->put('/gestion/proveedores/'.$provider->id, [...$data, 'status' => 'inactive'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('providers', ['id' => $provider->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'provider.created', 'subject_id' => $provider->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'provider.updated', 'subject_id' => $provider->id]);
    }

    public function test_provider_owner_must_be_a_supplier_and_url_must_be_https(): void
    {
        $this->actingAs($this->account(Role::Admin))->post('/gestion/proveedores', [
            'name' => 'Prueba', 'contact_email' => 'prueba@example.com', 'status' => 'active', 'owner_id' => $this->account(Role::Customer)->id, 'website' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['owner_id', 'website']);
        $this->assertDatabaseCount('providers', 0);
    }

    public function test_management_pages_are_denied_to_customers_and_unconfirmed_administrators(): void
    {
        $this->actingAs($this->account(Role::Customer));
        foreach (['proveedores', 'servicios', 'usuarios', 'auditoria', 'solicitudes', 'articulos'] as $page) {
            $this->get('/gestion/'.$page)->assertForbidden();
        }
        $this->post('/gestion/proveedores', [])->assertForbidden();
        $this->post('/gestion/servicios', [])->assertForbidden();
        $admin = $this->account(Role::Admin);
        $admin->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->actingAs($admin)->get('/gestion/proveedores')->assertRedirect(route('security.edit'));
    }

    public function test_supplier_cannot_read_or_edit_other_inventory_or_publish(): void
    {
        $supplier = $this->account(Role::Supplier);
        $own = $this->provider($supplier);
        $other = $this->provider($this->account(Role::Supplier));
        $otherService = TourismService::create($this->serviceData($other));
        $this->actingAs($supplier)->post('/gestion/servicios', $this->serviceData($own, 'review'))->assertSessionHasNoErrors();
        $ownService = TourismService::where('provider_id', $own->id)->firstOrFail();
        $this->get('/gestion/servicios')->assertInertia(fn (Assert $page) => $page->has('services.data', 1)->where('services.data.0.id', $ownService->id));
        $this->put('/gestion/servicios/'.$otherService->id, $this->serviceData($own))->assertForbidden();
        $this->put('/gestion/servicios/'.$ownService->id, $this->serviceData($other))->assertSessionHasErrors('provider_id');
        $this->put('/gestion/servicios/'.$ownService->id, $this->serviceData($own, 'published'))->assertSessionHasErrors('status');
        $this->assertDatabaseHas('tourism_services', ['id' => $ownService->id, 'status' => 'review', 'provider_id' => $own->id]);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_only_published_services_of_active_providers_are_public(): void
    {
        $active = $this->provider();
        $inactive = $this->provider(null, 'inactive');
        $visible = TourismService::create($this->serviceData($active, 'published'));
        $draft = TourismService::create($this->serviceData($active));
        $hidden = TourismService::create($this->serviceData($inactive, 'published'));
        $this->get('/explorar?category=actividades&destination=Popay&max_price=90000')->assertInertia(fn (Assert $page) => $page
            ->has('services.data', 1)->where('services.data.0.id', $visible->id)
            ->missing('services.data.0.provider.contact_email')->missing('services.data.0.provider.notes'));
        $this->get('/servicios/'.$visible->id)->assertOk();
        $this->get('/servicios/'.$draft->id)->assertNotFound();
        $this->get('/servicios/'.$hidden->id)->assertNotFound();
        $this->get('/explorar?max_price=100')->assertInertia(fn (Assert $page) => $page->has('services.data', 0));
        $this->get('/explorar?category=invalid')->assertSessionHasErrors('category');
    }

    public function test_admin_cannot_publish_inventory_for_an_inactive_provider(): void
    {
        $provider = $this->provider(null, 'inactive');
        $this->actingAs($this->account(Role::Admin))->post('/gestion/servicios', $this->serviceData($provider, 'published'))->assertSessionHasErrors('provider_id');
        $this->assertDatabaseCount('tourism_services', 0);
    }

    public function test_deactivating_a_provider_removes_its_services_from_public_search(): void
    {
        $provider = $this->provider();
        $service = TourismService::create($this->serviceData($provider, 'published'));
        $this->actingAs($this->account(Role::Admin))->put('/gestion/proveedores/'.$provider->id, [
            'name' => 'Operador local', 'contact_email' => 'operador@example.com', 'status' => 'inactive',
        ])->assertSessionHasNoErrors();
        $this->get('/servicios/'.$service->id)->assertNotFound();
        $this->get('/explorar')->assertInertia(fn (Assert $page) => $page->has('services.data', 0));
    }

    public function test_favorites_are_idempotent_private_and_removable(): void
    {
        $service = TourismService::create($this->serviceData($this->provider(), 'published'));
        $customer = $this->account(Role::Customer);
        $this->actingAs($customer)->post('/favoritos/'.$service->id)->assertRedirect();
        $this->post('/favoritos/'.$service->id)->assertRedirect();
        $this->assertDatabaseCount('favorites', 1);
        $this->get('/explorar?favorites=1')->assertInertia(fn (Assert $page) => $page->has('services.data', 1));
        $this->actingAs($this->account(Role::Customer))->get('/explorar?favorites=1')->assertInertia(fn (Assert $page) => $page->has('services.data', 0));
        $this->delete('/favoritos/'.$service->id);
        $this->assertDatabaseCount('favorites', 1);
        $this->actingAs($customer)->delete('/favoritos/'.$service->id);
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_customers_can_inquire_and_read_only_their_requests(): void
    {
        $service = TourismService::create($this->serviceData($this->provider(), 'published'));
        $customer = $this->account(Role::Customer);
        $this->actingAs($customer)->post('/servicios/'.$service->id.'/cotizar', [
            'travel_date' => now()->addDays(5)->toDateString(), 'travelers' => 2, 'message' => 'Quiero conocer los horarios y condiciones.',
            'status' => 'responded', 'user_id' => $this->account(Role::Customer)->id,
        ])->assertRedirect('/mis-solicitudes');
        $inquiry = TravelInquiry::firstOrFail();
        $this->assertSame($customer->id, $inquiry->user_id);
        $this->assertSame('pending', $inquiry->status);
        $this->get('/mis-solicitudes')->assertInertia(fn (Assert $page) => $page->has('inquiries.data', 1));
        $this->actingAs($this->account(Role::Customer))->get('/mis-solicitudes')->assertInertia(fn (Assert $page) => $page->has('inquiries.data', 0));
        $this->patch('/mis-solicitudes/'.$inquiry->id.'/cancelar')->assertForbidden();
    }

    public function test_inquiries_validate_dates_and_cannot_target_unpublished_services(): void
    {
        $service = TourismService::create($this->serviceData($this->provider(), 'published'));
        $this->actingAs($this->account(Role::Customer))->post('/servicios/'.$service->id.'/cotizar', [
            'travel_date' => now()->subDay()->toDateString(), 'travelers' => 0, 'message' => 'corto',
        ])->assertSessionHasErrors(['travel_date', 'travelers', 'message']);
        $service->update(['status' => 'draft']);
        $this->post('/servicios/'.$service->id.'/cotizar', [])->assertNotFound();
        $this->assertDatabaseCount('travel_inquiries', 0);
    }

    public function test_agent_response_is_visible_to_customer_and_closed_requests_are_immutable(): void
    {
        $customer = $this->account(Role::Customer);
        $inquiry = TravelInquiry::create(['user_id' => $customer->id, 'travel_date' => now()->addDay()->toDateString(), 'travelers' => 2, 'message' => 'Consulta de horarios.', 'status' => 'pending']);
        $agent = $this->account(Role::Agent);
        $this->actingAs($agent)->patch('/gestion/solicitudes/'.$inquiry->id, ['status' => 'responded', 'response' => ''])->assertSessionHasErrors('response');
        $this->patch('/gestion/solicitudes/'.$inquiry->id, ['status' => 'responded', 'response' => 'Por favor confirma la hora de salida deseada.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('travel_inquiries', ['id' => $inquiry->id, 'status' => 'responded', 'handled_by' => $agent->id]);
        $this->patch('/gestion/solicitudes/'.$inquiry->id, ['status' => 'in_progress'])->assertStatus(409);
        $this->actingAs($customer)->get('/mis-solicitudes')->assertInertia(fn (Assert $page) => $page->where('inquiries.data.0.response', 'Por favor confirma la hora de salida deseada.'));
        $this->patch('/mis-solicitudes/'.$inquiry->id.'/cancelar')->assertStatus(409);
    }

    public function test_customer_cancellation_cannot_be_overwritten_by_an_agent(): void
    {
        $customer = $this->account(Role::Customer);
        $inquiry = TravelInquiry::create(['user_id' => $customer->id, 'travel_date' => now()->addDay()->toDateString(), 'travelers' => 1, 'message' => 'Solicitud de información.']);
        $this->actingAs($customer)->patch('/mis-solicitudes/'.$inquiry->id.'/cancelar')->assertRedirect();
        $this->actingAs($this->account(Role::Agent))->patch('/gestion/solicitudes/'.$inquiry->id, ['status' => 'in_progress'])->assertStatus(409);
        $this->assertDatabaseHas('travel_inquiries', ['id' => $inquiry->id, 'status' => 'cancelled']);
    }

    public function test_only_superadministration_can_change_other_users_roles(): void
    {
        $target = $this->account(Role::Customer);
        $this->actingAs($this->account(Role::Admin))->patch('/gestion/usuarios/'.$target->id, ['role' => 'proveedor'])->assertForbidden();
        $super = $this->account(Role::SuperAdmin);
        $this->actingAs($super)->patch('/gestion/usuarios/'.$target->id, ['role' => 'proveedor'])->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('proveedor'));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $super->id, 'action' => 'user.role_changed', 'subject_id' => $target->id]);
        $this->patch('/gestion/usuarios/'.$super->id, ['role' => 'cliente'])->assertSessionHasErrors('role');
        $this->patch('/gestion/usuarios/'.$target->id, ['role' => 'invalid'])->assertSessionHasErrors('role');
    }

    public function test_management_lists_do_not_expose_passwords_or_two_factor_secrets(): void
    {
        $super = $this->account(Role::SuperAdmin);
        $this->actingAs($super)->get('/gestion/usuarios')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)->missing('users.data.0.password')->missing('users.data.0.two_factor_secret'));
        foreach (['proveedores', 'servicios', 'auditoria', 'solicitudes', 'articulos'] as $page) {
            $this->get('/gestion/'.$page)->assertOk();
        }
    }

    public function test_blog_drafts_and_future_articles_are_private_until_published(): void
    {
        $editor = $this->account(Role::Editor);
        $data = ['title' => 'Guía de viaje', 'slug' => 'guia-de-viaje', 'category' => 'Destinos', 'excerpt' => 'Ideas para preparar el viaje.', 'body' => 'Un artículo con consejos para preparar tu próxima visita.', 'published_at' => null];
        $this->actingAs($editor)->post('/gestion/articulos', $data)->assertSessionHasNoErrors();
        $post = Post::firstOrFail();
        $this->get('/blog/'.$data['slug'])->assertNotFound();
        $this->put('/gestion/articulos/'.$post->id, [...$data, 'published_at' => now()->addDay()->toDateTimeString()])->assertSessionHasNoErrors();
        $this->get('/blog/'.$data['slug'])->assertNotFound();
        $this->put('/gestion/articulos/'.$post->id, [...$data, 'published_at' => now()->subDay()->toDateTimeString()])->assertSessionHasNoErrors();
        $this->get('/blog/'.$data['slug'])->assertOk();
        $this->get('/blog?'.http_build_query(['q' => 'Guía']))->assertOk()->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.created', 'subject_id' => $post->id]);
    }

    public function test_supplier_contact_updates_are_scoped_and_cannot_change_activation_or_ownership(): void
    {
        $supplier = $this->account(Role::Supplier);
        $provider = $this->provider($supplier, 'pending');
        $other = $this->provider($this->account(Role::Supplier));
        $this->actingAs($supplier)->get('/gestion/mi-organizacion')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('providers', 1)->where('providers.0.id', $provider->id)->missing('providers.0.notes')->missing('providers.0.owner_id'));
        $data = ['contact_email' => 'actualizado@example.com', 'status' => 'active', 'owner_id' => $other->owner_id, 'notes' => 'Cambio no permitido'];
        $this->put('/gestion/mi-organizacion/'.$provider->id, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('providers', ['id' => $provider->id, 'contact_email' => 'actualizado@example.com', 'status' => 'pending', 'owner_id' => $supplier->id, 'notes' => 'Nota interna que no debe exponerse.']);
        $this->put('/gestion/mi-organizacion/'.$other->id, $data)->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'provider.contact_updated', 'subject_id' => $provider->id]);
    }

    public function test_publication_requires_admin_approval_and_supplier_edits_return_to_review(): void
    {
        $supplier = $this->account(Role::Supplier);
        $provider = $this->provider($supplier);
        $service = TourismService::create($this->serviceData($provider, 'review'));
        $this->actingAs($this->account(Role::Admin))->put('/gestion/servicios/'.$service->id, $this->serviceData($provider, 'published'))->assertSessionHasNoErrors();
        $this->get('/servicios/'.$service->id)->assertOk();
        $this->actingAs($supplier)->put('/gestion/servicios/'.$service->id, [...$this->serviceData($provider, 'review'), 'reference_price' => 100000])->assertSessionHasNoErrors();
        $this->get('/servicios/'.$service->id)->assertNotFound();
        $this->assertDatabaseHas('tourism_services', ['id' => $service->id, 'status' => 'review', 'reference_price' => 100000]);
    }

    public function test_catalog_sorting_and_pagination_keep_the_filters(): void
    {
        $provider = $this->provider();
        for ($i = 1; $i <= 14; $i++) {
            TourismService::create([...$this->serviceData($provider, 'published'), 'reference_price' => $i * 1000]);
        }
        $this->get('/explorar?sort=price_desc&category=actividades')->assertInertia(fn (Assert $page) => $page
            ->has('services.data', 12)->where('services.data.0.reference_price', 14000)
            ->where('services.total', 14)->where('filters.favorites', false));
        $this->get('/explorar?sort=price_asc&category=actividades&page=2')->assertInertia(fn (Assert $page) => $page
            ->has('services.data', 2)->where('services.data.0.reference_price', 13000));
    }
}
