export interface LeadPayload {
  name?: unknown;
  phone?: unknown;
  email?: unknown;
  source?: unknown;
  website?: unknown;
}

export interface SheetLead {
  token: string;
  name: string;
  phone: string;
  email: string;
  source: string;
}

export interface LeadResponse {
  ok?: boolean;
}
