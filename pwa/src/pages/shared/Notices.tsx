import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiNotices, NoticeItem } from '../../api/client'

const AUDIENCE_LABELS: Record<string, string> = {
  all: 'All', students: 'Students', parents: 'Parents',
}

export default function Notices() {
  const [page, setPage] = useState(1)

  const { data, isLoading, error } = useQuery({
    queryKey: ['notices', page],
    queryFn: () => apiNotices(page),
  })

  const notices: NoticeItem[] = data?.notices ?? []
  const pagination = data?.pagination

  return (
    <Layout title="Notices">
      <div className="space-y-4">
        {isLoading && <LoadingSpinner />}
        {error && <div className="card bg-red-50 border border-red-200 text-red-700 text-sm">Failed to load notices.</div>}

        {notices.length === 0 && !isLoading && (
          <div className="card text-center py-10 text-gray-400">
            <p className="text-3xl mb-2">🔔</p>
            <p className="text-sm">No notices available.</p>
          </div>
        )}

        {notices.map(n => (
          <NoticeCard key={n.id} notice={n} />
        ))}

        {/* Pagination */}
        {pagination && pagination.total_pages > 1 && (
          <div className="flex items-center justify-between pt-2">
            <button
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="btn-secondary px-4 py-2 disabled:opacity-40"
            >
              ← Previous
            </button>
            <span className="text-sm text-gray-500">
              Page {pagination.current_page} of {pagination.total_pages}
            </span>
            <button
              onClick={() => setPage(p => p + 1)}
              disabled={page >= pagination.total_pages}
              className="btn-secondary px-4 py-2 disabled:opacity-40"
            >
              Next →
            </button>
          </div>
        )}
      </div>
    </Layout>
  )
}

function NoticeCard({ notice }: { notice: NoticeItem }) {
  const [expanded, setExpanded] = useState(false)
  const body = notice.message ?? notice.body ?? ''

  return (
    <div className="card space-y-2">
      <div className="flex items-start justify-between gap-2">
        <h3 className="text-sm font-semibold text-gray-900 flex-1">{notice.title}</h3>
        {notice.audience && (
          <span className="badge badge-blue flex-shrink-0">{AUDIENCE_LABELS[notice.audience] ?? notice.audience}</span>
        )}
      </div>
      {notice.published_at && (
        <p className="text-xs text-gray-400">{notice.published_at}</p>
      )}
      <p className={`text-sm text-gray-600 ${expanded ? '' : 'line-clamp-3'}`}>{body}</p>
      {body.length > 140 && (
        <button
          onClick={() => setExpanded(e => !e)}
          className="text-xs text-primary-600 font-medium"
        >
          {expanded ? 'Show less' : 'Read more'}
        </button>
      )}
    </div>
  )
}
