export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
export type Owner = { id: number; name: string; email: string };
export type Provider = {
    id: number;
    owner_id: number | null;
    name: string;
    contact_email: string;
    phone: string | null;
    website: string | null;
    status: string;
    notes: string | null;
    owner?: Owner | null;
};
export type TourismService = {
    id: number;
    provider_id: number;
    title: string;
    category: string;
    destination: string;
    description: string;
    conditions: string | null;
    reference_price: number | null;
    currency: string;
    status: string;
    provider: { id: number; name: string };
};
export type Inquiry = {
    id: number;
    travel_date: string;
    travelers: number;
    message: string;
    status: string;
    response: string | null;
    service: { id: number; title: string; destination: string } | null;
    customer?: Owner;
    created_at: string;
};
export type Post = {
    id: number;
    title: string;
    slug: string;
    category: string;
    excerpt: string;
    body: string;
    published_at: string | null;
};
export const statusLabels: Record<string, string> = {
    pending: 'Pendiente',
    active: 'Activo',
    inactive: 'Inactivo',
    draft: 'Borrador',
    review: 'En revisión',
    published: 'Publicado',
    archived: 'Archivado',
    in_progress: 'En atención',
    responded: 'Respondida',
    cancelled: 'Cancelada',
    confirmed: 'Confirmada',
    expired: 'Vencida',
};
export const money = (value: number | null) =>
    value === null
        ? 'Precio por consultar'
        : new Intl.NumberFormat('es-CO', {
              style: 'currency',
              currency: 'COP',
              maximumFractionDigits: 0,
          }).format(value);
export const shortDate = (value: string) =>
    new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium' }).format(
        new Date(`${value.slice(0, 10)}T12:00:00`),
    );

export type Departure = {
    id: number;
    service_date: string;
    capacity?: number;
    occupied?: number | null;
    bookings_count?: number;
    available?: number;
    unit_price: number;
    terms: string;
    is_open?: boolean;
    quote_token?: string;
};
export type Booking = {
    payments?: { id: number; status: string; environment: string }[];
    id: number;
    reference: string;
    service_title: string;
    provider_name: string;
    service_date: string;
    travelers: number;
    unit_price: number;
    total: number;
    terms: string;
    status: string;
    expires_at: string;
    confirmed_at: string | null;
    cancellation_reason: string | null;
    customer?: Owner;
};
