import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiStudentResults } from '../../api/client'

export default function StudentResults() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['student-results'],
    queryFn: apiStudentResults,
  })

  const [selectedExamId, setSelectedExamId] = useState<number | null>(null)

  const exams = data?.exams ?? []
  const currentExam = selectedExamId !== null
    ? exams.find(e => e.id === selectedExamId)
    : exams[0] ?? null

  const marks = data?.marks ?? {}
  const currentMarks = currentExam ? (marks[currentExam.id] ?? []) : []

  const reportCard = data?.report_card ?? null

  const average = currentMarks.length
    ? Math.round(currentMarks.reduce((s, m) => s + (m.marks_obtained / m.total_marks) * 100, 0) / currentMarks.length)
    : null

  return (
    <Layout title="Results" showBack>
      <div className="space-y-5">
        {isLoading && <LoadingSpinner />}
        {error && <div className="card bg-red-50 border border-red-200 text-red-700 text-sm">Failed to load results.</div>}

        {/* Exam selector */}
        {exams.length > 0 && (
          <section>
            <h3 className="section-title">Select Exam</h3>
            <div className="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
              {exams.map(ex => (
                <button
                  key={ex.id}
                  onClick={() => setSelectedExamId(ex.id)}
                  className={`px-3 py-2 rounded-xl text-sm font-medium flex-shrink-0 transition-colors border ${
                    (selectedExamId === null ? exams[0]?.id : selectedExamId) === ex.id
                      ? 'bg-primary-600 text-white border-primary-600'
                      : 'bg-white text-gray-700 border-gray-200'
                  }`}
                >
                  {ex.name}
                </button>
              ))}
            </div>
          </section>
        )}

        {/* Current exam marks */}
        {currentExam && (
          <section>
            <div className="flex items-center justify-between mb-2">
              <h3 className="section-title mb-0">{currentExam.name}</h3>
              {average !== null && (
                <span className={`badge ${average >= 80 ? 'badge-green' : average >= 50 ? 'badge-yellow' : 'badge-red'}`}>
                  Avg {average}%
                </span>
              )}
            </div>

            {currentMarks.length === 0 ? (
              <div className="card text-sm text-gray-500 text-center py-6">No marks recorded yet.</div>
            ) : (
              <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
                <div className="grid grid-cols-12 px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                  <span className="col-span-6">Subject</span>
                  <span className="col-span-3 text-right">Score</span>
                  <span className="col-span-3 text-right">Grade</span>
                </div>
                {currentMarks.map((m, i) => {
                  const pct = Math.round((m.marks_obtained / m.total_marks) * 100)
                  return (
                    <div key={i} className="grid grid-cols-12 px-4 py-3 items-center">
                      <div className="col-span-6">
                        <p className="text-sm font-medium text-gray-900">{m.subject_name}</p>
                        <p className="text-xs text-gray-400">{m.exam_date ?? ''}</p>
                      </div>
                      <div className="col-span-3 text-right">
                        <p className="text-sm font-semibold text-gray-800">{m.marks_obtained}/{m.total_marks}</p>
                      </div>
                      <div className="col-span-3 text-right">
                        <span className={`badge ${pct >= 80 ? 'badge-green' : pct >= 50 ? 'badge-yellow' : 'badge-red'}`}>
                          {m.grade ?? `${pct}%`}
                        </span>
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </section>
        )}

        {/* Report Card */}
        {reportCard && (
          <section>
            <h3 className="section-title">Report Card</h3>
            <div className="card space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Academic Year</span>
                <span className="font-medium">{reportCard.academic_year}</span>
              </div>
              {reportCard.total_marks !== undefined && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Total Marks</span>
                  <span className="font-medium">{reportCard.total_marks_obtained} / {reportCard.total_marks}</span>
                </div>
              )}
              {reportCard.percentage !== undefined && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Percentage</span>
                  <span className={`font-bold ${reportCard.percentage >= 80 ? 'text-green-600' : reportCard.percentage >= 50 ? 'text-yellow-600' : 'text-red-600'}`}>
                    {reportCard.percentage}%
                  </span>
                </div>
              )}
              {reportCard.rank && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Rank</span>
                  <span className="font-medium">#{reportCard.rank}</span>
                </div>
              )}
              {reportCard.remarks && (
                <div className="text-sm border-t border-gray-100 pt-3">
                  <p className="text-gray-500 mb-1">Remarks</p>
                  <p className="text-gray-800">{reportCard.remarks}</p>
                </div>
              )}
            </div>
          </section>
        )}

        {!isLoading && exams.length === 0 && (
          <div className="card text-center py-10 text-gray-400">
            <p className="text-3xl mb-2">📋</p>
            <p className="text-sm">No exam results available yet.</p>
          </div>
        )}
      </div>
    </Layout>
  )
}
