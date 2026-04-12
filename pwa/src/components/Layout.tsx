import Header from './Header'
import BottomNav from './BottomNav'
import OfflineBanner from './OfflineBanner'

interface Props {
  title: string
  showBack?: boolean
  headerRight?: React.ReactNode
  children: React.ReactNode
  /** Extra padding at bottom (adds space above nav). Default true. */
  navPadding?: boolean
}

export default function Layout({ title, showBack, headerRight, children, navPadding = true }: Props) {
  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      <OfflineBanner />
      <Header title={title} showBack={showBack} right={headerRight} />
      <main className={`flex-1 max-w-mobile mx-auto w-full px-4 py-4 ${navPadding ? 'pb-24' : ''}`}>
        {children}
      </main>
      <BottomNav />
    </div>
  )
}
