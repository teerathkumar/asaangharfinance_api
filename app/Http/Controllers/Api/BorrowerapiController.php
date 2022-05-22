<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\LoanBorrower;
use App\Models\LoanHistory;
use App\Models\LoanGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BorrowerapiController extends Controller {

    public function index() {


        $data = LoanBorrower::all();
        return response()->json([
                    "code" => "01",
                    "message" => $data
                        ], 200);
    }

    public function addBorrower(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'fname' => 'required',
            'gender' => 'required',
            'dob' => 'required',
            'cnic' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'amount' => 'required',
            'loan_tenure' => 'required',
            'markup_rate' => 'required',
            'loan_frequency' => 'required',
            'disb_date' => 'required',
            'rep_start_date' => 'required',
            'loan_type_id' => 'required',
        ]);
   
        if($validator->fails()){
            return response()->json([
                                        "code" => "04",
                                        "message" => "Validation Error",
                'data'=>$validator->errors()
                                            ], 201);            
            //return $this->sendError('Validation Error.', $validator->errors());       
        }



        $borrower = new LoanBorrower;
        //
        $existBorrowerId = LoanBorrower::where(['cnic' => $request->cnic])->first("id");
        if ($existBorrowerId) {
            $borrowerId = $existBorrowerId->id;
            $loanStatus = LoanHistory::where(["borrower_id" => $borrowerId, "loan_status_id" => 1])->first("loan_status_id");
            if ($loanStatus && $loanStatus->loan_status_id == 1) {
                return response()->json([
                            "code" => "02",
                            "message" => "Already Disbursed"
                                ], 201);
            }
        }

        DB::beginTransaction();

        try {
            if ($existBorrowerId) {
                $borrower->id = $borrowerId;
            }
            $borrower->fname = $request->fname;
            $borrower->mname = $request->mname ? $request->mname : "";
            $borrower->lname = $request->lname ? $request->lname : "";
            $borrower->gender = $request->gender;
            $borrower->dob = $request->dob;
            $borrower->caste = isset($request->caste) ? $request->caste : "";
            $borrower->cnic = $request->cnic;
            $borrower->mobile = $request->mobile;
            $borrower->address = $request->address;
            //$borrower->save();
            if ($existBorrowerId) {
                $borrower->update();
                //$borrower->id = $borrowerId;
            } else {
                $borrower->save();
            }

            $GroupName = $request->fname . " " . $request->mname . " " . $request->lname;
            $GroupCode = preg_replace('/\s+/', '_', $GroupName). date("YmdHis");
            

            $group = new LoanGroup;
            $group->name = $GroupName;
            $group->code = $GroupCode;
            $group->save();

            $loan = new LoanHistory;
            $loan->borrower_id = $borrower->id;
            $loan->group_id = $group->id;
            $loan->office_id = 2;
            $loan->total_amount_pr = $request->amount;
            $loan->loan_period = $request->loan_tenure;
            $loan->markup_rate = $request->markup_rate;
            $loan->loan_frequency = $request->loan_frequency;
            $loan->loan_status_id = 1;
            $loan->disb_date = $request->disb_date;
            $loan->rep_start_date = $request->rep_start_date;
            $loan->save();

            DB::commit();
            return response()->json([
                        "code" => "01",
                        "message" => "Transaction Successful"
                            ], 201);
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                        "code" => "00",
                        "message" => "Transaction Failed",
                "data"=>$e->getMessage()
                            ], 201);
        }
    }

}
