import { useState, useRef, FormEvent } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiMessages, apiSendMessage, Message } from '../../api/client'
import { useAuth } from '../../contexts/AuthContext'

function MessageBubble({ msg, myId }: { msg: Message; myId: number }) {
  const isMine = msg.sender_id === myId
  return (
    <div className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
      <div
        className={`max-w-[80%] rounded-2xl px-4 py-2 text-sm break-words ${
          isMine
            ? 'bg-primary-600 text-white rounded-br-sm'
            : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'
        }`}
      >
        {!isMine && <p className="text-xs font-semibold mb-0.5 opacity-70">{msg.sender_name}</p>}
        <p>{msg.body}</p>
        <p className={`text-[10px] mt-1 text-right ${isMine ? 'text-primary-200' : 'text-gray-400'}`}>
          {msg.sent_at}
        </p>
      </div>
    </div>
  )
}

export default function Messages() {
  const { user } = useAuth()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [receiverId, setReceiverId] = useState('')
  const [messageBody, setMessageBody] = useState('')
  const [sendError, setSendError] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['messages', page],
    queryFn: () => apiMessages(page),
    refetchInterval: 30_000, // poll every 30s
  })

  const mutation = useMutation({
    mutationFn: ({ to, body }: { to: number; body: string }) => apiSendMessage(to, body),
    onSuccess: () => {
      setMessageBody('')
      setSendError('')
      qc.invalidateQueries({ queryKey: ['messages'] })
    },
    onError: (err: any) => {
      setSendError(err?.error ?? 'Failed to send message.')
    },
  })

  function handleSend(e: FormEvent) {
    e.preventDefault()
    const toId = parseInt(receiverId, 10)
    if (!toId || !messageBody.trim()) return
    mutation.mutate({ to: toId, body: messageBody.trim() })
  }

  const messages: Message[] = data?.messages ?? []
  const userId = user?.id ?? 0

  return (
    <Layout title="Messages">
      <div className="space-y-4">
        {isLoading && <LoadingSpinner />}

        {messages.length === 0 && !isLoading && (
          <div className="card text-center py-10 text-gray-400">
            <p className="text-3xl mb-2">💬</p>
            <p className="text-sm">No messages yet.</p>
          </div>
        )}

        {/* Message list */}
        {messages.length > 0 && (
          <div className="space-y-2">
            {messages.map(msg => (
              <MessageBubble key={msg.id} msg={msg} myId={userId} />
            ))}
          </div>
        )}

        {/* Pagination */}
        {data?.pagination && data.pagination.total_pages > 1 && (
          <div className="flex items-center justify-between">
            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page <= 1} className="btn-secondary text-sm disabled:opacity-40">← Older</button>
            <span className="text-xs text-gray-500">Page {data.pagination.current_page}/{data.pagination.total_pages}</span>
            <button onClick={() => setPage(p => p + 1)} disabled={page >= (data?.pagination?.total_pages ?? 1)} className="btn-secondary text-sm disabled:opacity-40">Newer →</button>
          </div>
        )}

        {/* Compose */}
        <section>
          <h3 className="section-title">New Message</h3>
          <form onSubmit={handleSend} className="card space-y-3">
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Recipient User ID</label>
              <input
                ref={inputRef}
                className="input"
                type="number"
                min="1"
                placeholder="Enter recipient user ID"
                value={receiverId}
                onChange={e => setReceiverId(e.target.value)}
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Message</label>
              <textarea
                className="input resize-none h-20"
                placeholder="Type your message…"
                value={messageBody}
                onChange={e => setMessageBody(e.target.value)}
                maxLength={1000}
                required
              />
            </div>
            {sendError && <p className="text-xs text-red-600">{sendError}</p>}
            <button
              type="submit"
              disabled={mutation.isPending || !receiverId || !messageBody.trim()}
              className="btn-primary w-full"
            >
              {mutation.isPending ? 'Sending…' : 'Send Message'}
            </button>
          </form>
        </section>
      </div>
    </Layout>
  )
}
