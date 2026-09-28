import { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react'
import { createConversation, fetchConversation, sendConversationMessage } from '../api/conversations'

const STORAGE_KEY = 'support-conversation-id'
const ChatContext = createContext(null)

const storage = {
  get: () => {
    try {
      return localStorage.getItem(STORAGE_KEY)
    } catch {
      return null
    }
  },
  set: (id) => {
    try {
      localStorage.setItem(STORAGE_KEY, id)
    } catch {
      // Storage unavailable (private mode): the conversation simply won't survive a reload.
    }
  },
}

/**
 * One support conversation shared by the floating widget and the /support page,
 * so a customer can move between them without losing their place.
 *
 * @param {{ children: import('react').ReactNode }} props
 */
export function ChatProvider({ children }) {
  const [conversation, setConversation] = useState({ id: null, stage: null, messages: [] })
  const [status, setStatus] = useState('idle')
  const [error, setError] = useState(null)
  const startedRef = useRef(false)

  const begin = useCallback(async () => {
    setStatus('loading')
    setError(null)
    try {
      const { data } = await createConversation()
      storage.set(data.id)
      setConversation(data)
      setStatus('ready')
    } catch (err) {
      setError(err.message)
      setStatus('error')
    }
  }, [])

  const ensureStarted = useCallback(async () => {
    if (startedRef.current) return
    startedRef.current = true

    const savedId = storage.get()
    if (!savedId) {
      await begin()
      return
    }

    setStatus('loading')
    try {
      const { data } = await fetchConversation(savedId)
      setConversation(data)
      setStatus('ready')
    } catch {
      await begin()
    }
  }, [begin])

  const send = useCallback(async (content) => {
    const text = content.trim()
    if (!text || !conversation.id) return

    const pendingId = `pending-${Date.now()}`
    setError(null)
    setStatus('sending')
    setConversation((current) => ({
      ...current,
      messages: [...current.messages, { id: pendingId, role: 'customer', content: text, quick_replies: [] }],
    }))

    try {
      const { data } = await sendConversationMessage(conversation.id, text)
      setConversation((current) => ({
        ...current,
        stage: data.stage,
        messages: [...current.messages.filter((message) => message.id !== pendingId), ...data.messages],
      }))
    } catch (err) {
      setConversation((current) => ({ ...current, messages: current.messages.filter((message) => message.id !== pendingId) }))
      setError(err.errors?.content?.[0] ?? err.message)
    } finally {
      setStatus('ready')
    }
  }, [conversation.id])

  const value = useMemo(
    () => ({ conversation, status, error, ensureStarted, send, startNew: begin }),
    [conversation, status, error, ensureStarted, send, begin],
  )

  return <ChatContext.Provider value={value}>{children}</ChatContext.Provider>
}

export function useChat() {
  return useContext(ChatContext)
}
