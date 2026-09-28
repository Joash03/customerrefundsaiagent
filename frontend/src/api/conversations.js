import { apiRequest } from './client'

export const createConversation = () => apiRequest('/conversations', { method: 'POST' })

export const fetchConversation = (id) => apiRequest(`/conversations/${id}`)

export const sendConversationMessage = (id, content) =>
  apiRequest(`/conversations/${id}/messages`, { method: 'POST', body: { content } })
