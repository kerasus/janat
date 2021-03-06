import { Model, Collection } from 'js-abstract-model'
import {Transaction, TransactionList} from "./Transaction";

class AllocatedLoanInstallment extends Model {
    constructor (user) {
        super(user, [
            {
                key: 'baseRoute',
                default: 'api/allocated_loan_Installments'
            },
            { key: 'id' },
            { key: 'allocated_loan' },
            { key: 'allocated_loan_id' },
            { key: 'rate' },
            { key: 'is_settled' },
            { key: 'total_payments' },
            {
                key: 'last_payment',
                relatedModel: Transaction
            },
            { key: 'remaining_payable_amount' },
            {
                key: 'received_transactions',
                relatedModel: TransactionList
            },
            { key: 'created_at' },
            { key: 'updated_at' },
            { key: 'deleted_at' }
        ])
    }

}

class AllocatedLoanInstallmentList extends Collection {
    model () {
        return AllocatedLoanInstallment
    }
}

export { AllocatedLoanInstallment, AllocatedLoanInstallmentList }
