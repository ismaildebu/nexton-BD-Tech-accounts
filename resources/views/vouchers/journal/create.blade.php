<x-app-layout>

<div class="container py-4">

    <div class="card">

        <div class="card-header">
            <h4>Journal Voucher</h4>
        </div>


        <div class="card-body">

            <form method="POST"
                  action="{{ route('journal-vouchers.store') }}">

                @csrf


                <div class="row mb-3">

                    <div class="col-md-3">
                        <label>Voucher Type</label>

                        <input type="text"
                               class="form-control"
                               value="{{ $voucherType->voucher_name ?? '' }}"
                               readonly>
                    </div>


                    <div class="col-md-3">

                        <label>Voucher No</label>

                        <input type="text"
                               name="voucher_no"
                               class="form-control"
                               value="JV-00001"
                               readonly>

                    </div>


                    <div class="col-md-3">

                        <label>Financial Year</label>

                        <input type="text"
                               class="form-control"
                               value="{{ $financialYear->name ?? '' }}"
                               readonly>

                    </div>


                    <div class="col-md-3">

                        <label>Date</label>

                        <input type="date"
                               name="transaction_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}">

                    </div>

                </div>



                <div class="mb-3">

                    <label>Narration</label>

                    <textarea name="narration"
                              class="form-control"></textarea>

                </div>



                <hr>


                <h5>Accounting Entries</h5>


                <table class="table table-bordered"
                       id="entryTable">

                    <thead>

                    <tr>

                        <th>
                            Account
                        </th>

                        <th width="200">
                            Debit
                        </th>

                        <th width="200">
                            Credit
                        </th>

                        <th width="80">
                            Action
                        </th>

                    </tr>

                    </thead>


                    <tbody>


                    <tr>

                        <td>

                            <select name="accounts[]"
                                    class="form-control">

                                <option value="">
                                    Select Account
                                </option>


                                @foreach($accounts as $account)

                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                                                                
                                @endforeach


                            </select>


                        </td>


                        <td>

                            <input type="number"
                                   step="0.01"
                                   name="debits[]"
                                   class="form-control debit"
                                   value="0">

                        </td>


                        <td>

                            <input type="number"
                                   step="0.01"
                                   name="credits[]"
                                   class="form-control credit"
                                   value="0">

                        </td>


                        <td>

                            <button type="button"
                                    class="btn btn-danger removeRow">
                                X
                            </button>

                        </td>


                    </tr>


                    </tbody>


                </table>



                <button type="button"
                        class="btn btn-success"
                        id="addRow">

                    + Add Row

                </button>


                <hr>


                <div class="row">


                    <div class="col-md-6">

                        <h5>
                            Total Debit:
                            <span id="totalDebit">
                                0.00
                            </span>
                        </h5>

                    </div>


                    <div class="col-md-6">

                        <h5>
                            Total Credit:
                            <span id="totalCredit">
                                0.00
                            </span>
                        </h5>

                    </div>


                </div>



                <button type="submit"
                        class="btn btn-primary mt-3">

                    Save Journal Voucher

                </button>


            </form>


        </div>

    </div>


</div>




<script>


document.getElementById('addRow')
.addEventListener('click', function(){

    let table =
    document.querySelector('#entryTable tbody');

    let row =
    table.rows[0].cloneNode(true);

    // Input Reset
   row.querySelectorAll('input').forEach(input => {
    input.value = 0;
    input.readOnly = false;
});

    // Select Reset
    row.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });

    table.appendChild(row);

    calculateTotal();

});



document.addEventListener(
'click',
function(e){


    if(e.target.classList.contains('removeRow')){


        let rows =
        document.querySelectorAll('#entryTable tbody tr');


        if(rows.length > 1){

            e.target.closest('tr').remove();

            calculateTotal();

        }

    }


});



document.addEventListener('input', function(e) {

    if (e.target.classList.contains('debit')) {

        let row = e.target.closest('tr');
        let credit = row.querySelector('.credit');

        if (parseFloat(e.target.value) > 0) {
            credit.value = 0;
            credit.readOnly = true;
        } else {
            credit.readOnly = false;
        }

    }

    if (e.target.classList.contains('credit')) {

        let row = e.target.closest('tr');
        let debit = row.querySelector('.debit');

        if (parseFloat(e.target.value) > 0) {
            debit.value = 0;
            debit.readOnly = true;
        } else {
            debit.readOnly = false;
        }

    }

    calculateTotal();

});




function calculateTotal(){


    let debit = 0;
    let credit = 0;


    document.querySelectorAll('.debit')
    .forEach(function(input){

        debit += Number(input.value);

    });


    document.querySelectorAll('.credit')
    .forEach(function(input){

        credit += Number(input.value);

    });


    document.getElementById('totalDebit')
    .innerHTML = debit.toFixed(2);


    document.getElementById('totalCredit')
    .innerHTML = credit.toFixed(2);


}

document.querySelector('form')
.addEventListener('submit', function(e){

let accountSelects = document.querySelectorAll('select[name="accounts[]"]');

for (let select of accountSelects) {

    if (select.value === '') {

        alert('Please select an account for all entries.');

        select.focus();

        e.preventDefault();

        return;

    }

}
    let debit =
    Number(document.getElementById('totalDebit').innerHTML);


    let credit =
    Number(document.getElementById('totalCredit').innerHTML);



    if(debit <= 0 || credit <= 0){


        alert(
            'Debit and Credit amount required'
        );


        e.preventDefault();

        return;


    }



    if(debit !== credit){


        alert(
            'Debit and Credit must be equal'
        );


        e.preventDefault();

        return;

    }


});

calculateTotal();

</script>


</x-app-layout>