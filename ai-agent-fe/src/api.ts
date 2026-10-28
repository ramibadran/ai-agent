const baseUrl = import.meta.env.VITE_API_URL ?? '/api/v1'

type ApiResponse<T> = { success: boolean; message: string; data: T; authorization?: { token: string } }

export type Message = { id: number; role: 'user' | 'assistant'; content: string; sources?: { title: string; score: number; source_url?: string | null; chunk_number: number }[] }
export type Conversation = { id: number; title: string; messages?: Message[]; messages_count?: number }

async function request<T>(path: string, options: RequestInit = {}): Promise<ApiResponse<T>> {
  const token = localStorage.getItem('ai_agent_token')
  const response = await fetch(`${baseUrl}${path}`, { ...options, headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...options.headers } })
  const payload = await response.json() as ApiResponse<T>
  if (!response.ok) throw new Error(Array.isArray(payload.message) ? payload.message[0] : payload.message)
  return payload
}

export const api = {
  login: (email: string, password: string) => request<{ id: number; name: string; email: string }>('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }),
  conversations: () => request<Conversation[]>('/chat/conversations'),
  conversation: (id: number) => request<Conversation>(`/chat/conversations/${id}`),
  createConversation: (message: string) => request<Conversation>('/chat/conversations', { method: 'POST', body: JSON.stringify({ message }) }),
  sendMessage: (id: number, message: string) => request<{ user_message: Message; assistant_message: Message }>(`/chat/conversations/${id}/messages`, { method: 'POST', body: JSON.stringify({ message }) }),
  deleteConversation: (id: number) => request<null>(`/chat/conversations/${id}`, { method: 'DELETE' }),
}
