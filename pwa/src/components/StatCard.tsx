interface Props {
  title: string
  value: string | number
  subtitle?: string
  icon?: string
  accent?: 'blue' | 'green' | 'orange' | 'red' | 'purple'
}

const accents: Record<string, string> = {
  blue:   'bg-primary-50 border-primary-200 text-primary-700',
  green:  'bg-green-50 border-green-200 text-green-700',
  orange: 'bg-orange-50 border-orange-200 text-orange-700',
  red:    'bg-red-50 border-red-200 text-red-700',
  purple: 'bg-purple-50 border-purple-200 text-purple-700',
}

export default function StatCard({ title, value, subtitle, icon, accent = 'blue' }: Props) {
  return (
    <div className={`card border ${accents[accent]} flex items-start gap-3`}>
      {icon && <span className="text-2xl leading-none mt-0.5">{icon}</span>}
      <div className="min-w-0">
        <p className="text-xs font-medium uppercase tracking-wide opacity-70">{title}</p>
        <p className="text-2xl font-bold leading-tight">{value}</p>
        {subtitle && <p className="text-xs opacity-60 mt-0.5 truncate">{subtitle}</p>}
      </div>
    </div>
  )
}
