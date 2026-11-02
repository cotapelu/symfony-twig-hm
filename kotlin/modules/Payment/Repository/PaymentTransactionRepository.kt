package payment

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class RefundRepository {
    private val refunds = ConcurrentHashMap<String, Refund>()

    fun save(refund: Refund): Refund {
        refunds[refund.id] = refund
        return refund
    }

    fun findById(id: String): Refund? {
        return refunds[id]
    }

    fun findByPaymentId(paymentId: String): List<Refund> {
        return refunds.values.filter { it.paymentId == paymentId }
    }

    fun update(refund: Refund): Refund {
        refunds[refund.id] = refund
        return refund
    }

    fun getRefundStats(userId: String): Map<String, Any> {
        val userRefunds = refunds.values.filter { it.paymentId.startsWith(userId) }
        return mapOf(
            "totalRefunds" to userRefunds.size,
            "totalRefundedAmount" to userRefunds.filter { it.status == "completed" }.sumOf { it.amount },
            "pendingRefunds" to userRefunds.filter { it.status == "pending" }.size,
            "failedRefunds" to userRefunds.filter { it.status == "failed" }.size
        )
    }
}

class PaymentMethodRepository {
    private val paymentMethods = ConcurrentHashMap<String, PaymentMethod>()

    fun save(paymentMethod: PaymentMethod): PaymentMethod {
        paymentMethods[paymentMethod.id] = paymentMethod
        return paymentMethod
    }

    fun findById(id: String): PaymentMethod? {
        return paymentMethods[id]
    }

    fun findByUserId(userId: String): List<PaymentMethod> {
        return paymentMethods.values.filter { it.userId == userId }
    }

    fun findByType(type: String): List<PaymentMethod> {
        return paymentMethods.values.filter { it.type == type }
    }

    fun findDefault(userId: String): PaymentMethod? {
        return paymentMethods.values.find { it.userId == userId && it.isDefault }
    }

    fun setDefault(userId: String, paymentMethodId: String) {
        paymentMethods.values.filter { it.userId == userId }.forEach { it.isDefault = false }
        paymentMethods[paymentMethodId]?.isDefault = true
    }

    fun delete(id: String) {
        paymentMethods.remove(id)
    }
}

class PaymentTransactionRepository {
    private val transactions = ConcurrentHashMap<String, PaymentTransaction>()

    fun save(transaction: PaymentTransaction): PaymentTransaction {
        transactions[transaction.id] = transaction
        return transaction
    }

    fun findById(id: String): PaymentTransaction? {
        return transactions[id]
    }

    fun findByPaymentId(paymentId: String): List<PaymentTransaction> {
        return transactions.values.filter { it.paymentId == paymentId }
    }

    fun findByUserId(userId: String): List<PaymentTransaction> {
        return transactions.values.filter { it.userId == userId }
    }

    fun findByStatus(status: String): List<PaymentTransaction> {
        return transactions.values.filter { it.status == status }
    }

    fun updateStatus(id: String, status: String): PaymentTransaction? {
        val transaction = transactions[id]
        if (transaction != null) {
            transaction.status = status
            transaction.updatedAt = LocalDateTime.now()
            transactions[id] = transaction
        }
        return transaction
    }
}