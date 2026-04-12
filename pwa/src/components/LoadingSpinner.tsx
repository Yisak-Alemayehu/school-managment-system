interface Props {
  fullScreen?: boolean
}

export default function LoadingSpinner({ fullScreen }: Props) {
  const spinner = (
    <div className="flex items-center justify-center">
      <div className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin" />
    </div>
  )

  if (fullScreen) {
    return (
      <div className="fixed inset-0 bg-white flex items-center justify-center z-50">
        {spinner}
      </div>
    )
  }

  return <div className="py-12">{spinner}</div>
}
