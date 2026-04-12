import { useNavigate } from 'react-router-dom'

interface Props {
  title: string
  showBack?: boolean
  right?: React.ReactNode
}

export default function Header({ title, showBack, right }: Props) {
  const navigate = useNavigate()

  return (
    <header className="sticky top-0 z-30 bg-white border-b border-gray-200 safe-top">
      <div className="flex items-center h-14 px-4 gap-3 max-w-mobile mx-auto">
        {showBack && (
          <button
            onClick={() => navigate(-1)}
            className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 -ml-1"
            aria-label="Go back"
          >
            <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}
        <h1 className="flex-1 text-lg font-semibold text-gray-900 truncate">{title}</h1>
        {right && <div className="flex items-center gap-1">{right}</div>}
      </div>
    </header>
  )
}
