import { useQuery } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiParentFees, FeeRecord, Transaction } from '../../api/client'
import { useAuth } from '../../contexts/AuthContext'

function statusBadge(status: string) {
  switch (status?.toLowerCase()) {
    case 'paid': return 'badge-green'
    case 'partial': return 'badge-yellow'
    default: return 'badge-red'
  }
}

export default function ParentFees() {
  const { guardian, activeChild } = useAuth()

  const { data, isLoading, error } = useQuery({
    queryKey: ['parent-fees', guardian?.id, activeChild?.student_id],
    queryFn: () => apiParentFees(guardian!.id, activeChild!.student_id),
    enabled: !!guardian?.id && !!activeChild?.student_id,
  })

  const fees: FeeRecord[] = data?.fees ?? []
  const transactions: Transaction[] = data?.transactions ?? []

  return (
    <Layout title="Fees" showBack>
      <div className="space-y-5">
        {/* Child name */}
        {activeChild && (
          <div className="text-sm text-gray-500">
            Showing fees for <span className="font-semibold text-gray-800">{activeChild.first_name} {activeChild.last_name}</span>
          </div>
        )}

        {isLoading && <LoadingSpinner />}
        {error && <div className="card bg-red-50 border border-red-200 text-red-700 text-sm">Failed to load fee data.</div>}

        {/* Summary */}
        {data && (
          <div className="card bg-primary-50 border border-primary-200 space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Total Fees</span>
              <span className="font-semibold">{(data.total_amount ?? 0).toFixed(2)} ETB</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Amount Paid</span>
              <span className="font-semibold text-green-700">{(data.total_paid ?? 0).toFixed(2)} ETB</span>
            </div>
            <div className="flex justify-between text-sm border-t border-primary-200 pt-2 mt-1">
              <span className="font-semibold text-gray-700">Balance Due</span>
              <span className={`font-bold text-lg ${(data.total_balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600'}`}>
                {(data.total_balance ?? 0).toFixed(2)} ETB
              </span>
            </div>
          </div>
        )}

        {/* Fee records */}
        {fees.length > 0 && (
          <section>
            <h3 className="section-title">Fee Breakdown</h3>
            <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
              {fees.map(fee => (
                <div key={fee.id} className="px-4 py-3 space-y-1">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-medium text-gray-900">{fee.fee_name}</p>
                    <span className={`badge ${statusBadge(fee.status)}`}>{fee.status}</span>
                  </div>
                  <div className="flex justify-between text-xs text-gray-500">
                    <span>Due: {fee.amount?.toFixed(2)} ETB</span>
                    <span>Paid: {(fee.paid_amount ?? 0).toFixed(2)} ETB</span>
                  </div>
                  {fee.due_date && (
                    <p className="text-xs text-gray-400">Due date: {fee.due_date}</p>
                  )}
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Transactions */}
        {transactions.length > 0 && (
          <section>
            <h3 className="section-title">Payment History</h3>
            <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
              {transactions.map((tx, i) => (
                <div key={i} className="flex items-center justify-between px-4 py-3">
                  <div>
                    <p className="text-sm font-medium text-gray-900">
                      {tx.payment_method ?? 'Payment'}
                    </p>
                    <p className="text-xs text-gray-400">{tx.paid_at ?? tx.created_at}</p>
                    {tx.reference && <p className="text-xs text-gray-400">Ref: {tx.reference}</p>}
                  </div>
                  <span className="text-sm font-bold text-green-700">+{(tx.amount).toFixed(2)} ETB</span>
                </div>
              ))}
            </div>
          </section>
        )}

        {!isLoading && fees.length === 0 && (
          <div className="card text-center py-10 text-gray-400">
            <p className="text-3xl mb-2">💵</p>
            <p className="text-sm">No fee records found.</p>
          </div>
        )}
      </div>
    </Layout>
  )
}
