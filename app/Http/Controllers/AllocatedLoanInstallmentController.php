<?php

namespace App\Http\Controllers;

use App\AllocatedLoan;
use App\AllocatedLoanInstallment;
use App\Classes\LoanCalculator;
use App\Http\Requests\StoreAllocatedLoanInstallment;
use App\Traits\CommonCRUD;
use App\Traits\Filter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AllocatedLoanInstallmentController extends Controller
{
    use Filter, CommonCRUD;

    public function __construct()
    {
        $this->middleware('can:view accounts', ['only' => ['showPeriodicPayrollDeductionForChargeFund']]);
        $this->middleware('can:create accounts', ['only' => ['store']]);
        $this->middleware('can:edit accounts', ['only' => ['update', 'payPeriodicPayrollDeductionForChargeFund', 'rollbackPayPeriodicPayrollDeduction']]);
        $this->middleware('can:delete accounts', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $config = [
            'filterRelationIds'=> [
                [
                    'requestKey' => 'user_id',
                    'relationName' => 'allocatedLoan.account.user'
                ]
            ],
            'eagerLoads'=> [
                'allocatedLoan',
                'allocatedLoan.account',
                'allocatedLoan.loan',
                'allocatedLoan.account.user:id,f_name,l_name',
            ]
        ];

        if(!Auth::user()->can('view allocated_loan_installments')) {
            $request->offsetSet('user_id', Auth::user()->id);
        }

        return $this->commonIndex($request,
            AllocatedLoanInstallment::class,
            $config
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAllocatedLoanInstallment $request
     * @return Response
     */
    public function store(StoreAllocatedLoanInstallment $request)
    {
        $allocatedLoan = AllocatedLoan::with('installments')
            ->findOrFail($request->get('allocated_loan_id'))
            ->setAppends(['has_unsettled_installment']);
        if ($allocatedLoan->has_unsettled_installment) {
            return $this->jsonResponseValidateError([
                'errors' => [
                    'has_unsettled_installment' => [
                        'برای این وام قسط پرداخت نشده وجود دارد.'
                    ]
                ]
            ]);
        }
        $loanCalculator = new LoanCalculator();
        $installmentRate = $allocatedLoan->installment_rate;
        if ($loanCalculator->isTimeToPayLastInstallment($allocatedLoan)) {
            $lastInstallmentRate = $loanCalculator->getLastInstallmentRate($allocatedLoan);
            $installmentRate = $lastInstallmentRate;
        }

        $allocatedLoanInstallment = AllocatedLoanInstallment::create([
            'allocated_loan_id' => $allocatedLoan->id,
            'rate' => $installmentRate
        ]);
        return $this->show($allocatedLoanInstallment->id);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        $allocatedLoanInstallment = AllocatedLoanInstallment::with('receivedTransactions.transactionStatus')
            ->findOrFail($id)
            ->setAppends([
                'is_settled',
                'total_payments',
                'remaining_payable_amount'
            ]);

        $this->checkOwner($allocatedLoanInstallment->allocatedLoan->account->user->id);

        return $this->jsonResponseOk($allocatedLoanInstallment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param AllocatedLoanInstallment $allocatedLoanInstallment
     * @return Response
     */
    public function update(Request $request, AllocatedLoanInstallment $allocatedLoanInstallment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Response
     */
    public function destroy($id)
    {
        Validator::make([
            'allocatedLoanInstallment_id' => $id
        ], [
            'allocatedLoanInstallment_id' => 'required|exists:allocated_loan_installments,id'
        ])->validate();

        $allocatedLoanInstallment = AllocatedLoanInstallment::find($id)->setAppends(['totalPayments']);

        $errors = [];
        if ($allocatedLoanInstallment->totalPayments > 0) {
            $errors['has_paid_transaction'] = [
                'قسط مورد نظر دارای تراکنش پرداخت شده است.'
            ];
        }

        if (count($errors) > 0) {
            return $this->jsonResponseValidateError([
                'errors' => $errors
            ]);
        }

        if (AllocatedLoanInstallment::find($id)->delete()) {
            return $this->jsonResponseOk([ 'message'=> 'حذف با موفقیت انجام شد.' ]);
        } else {
            return $this->jsonResponseServerError([
                'errors' => [
                    'allocatedLoanInstallment_destroy' => [
                        'مشکلی در حذف اطلاعات رخ داده است.'
                    ]
                ]
            ]);
        }
    }
}
