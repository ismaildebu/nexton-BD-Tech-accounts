@extends('layouts.app')

@section('title','Create New Company')

@section('page-title','Create New Company')

@section('page-subtitle','Create a new company')

@section('content')

    <div class="bg-white shadow rounded-lg p-6">

        <h3 class="text-2xl font-bold mb-6">
            Create New Company
        </h3>


        @if ($errors->any())

            <div class="bg-red-100 text-red-700 p-4 rounded mb-4">

                <ul>
                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach
                </ul>

            </div>

        @endif



        <form action="{{ route('companies.store') }}" method="POST">

            @csrf


            <!-- Company Name -->

            <div class="mb-5">

                <label class="block font-medium mb-2">
                    Company Name
                </label>


                <input
                    type="text"
                    name="company_name"
                    class="w-full border rounded px-3 py-2"
                    placeholder="Enter Company Name"
                    required>

            </div>




            <!-- Business Type -->

            <div class="mb-5">

                <label class="block font-medium mb-2">
                    Business Type
                </label>


                <select
                    id="business_type"
                    name="business_type"
                    class="w-full border rounded px-3 py-2"
                    required>


                    <option value="">
                        -- Select Business Type --
                    </option>


                    <option value="Trading">
                        Trading Business
                    </option>


                    <option value="Service">
                        Service Business
                    </option>


                    <option value="Manufacturing">
                        Manufacturing
                    </option>


                    <option value="Hospital">
                        Hospital / Clinic
                    </option>


                    <option value="Education">
                        School / College
                    </option>

                    <option value="Media">
                        News Paper / Media
                    </option>
                </select>


            </div>





            <!-- Accounts -->

            <div class="mb-5">


                <div class="flex justify-between mb-3">

                    <label class="font-medium text-lg">
                        Select Accounts
                    </label>


                    <label>
                        <input 
                        type="checkbox" 
                        id="selectAll">

                        Select All

                    </label>

                </div>




                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">


                @foreach($accountTemplates as $account)


                    <label 
                    class="account-item border rounded p-3 flex items-center gap-3"
                    data-industry="{{ $account->industry }}">


                        <input

                        type="checkbox"

                        name="accounts[]"

                        value="{{ $account->id }}"

                        class="account-checkbox rounded">


                        <span>

                            {{ $account->account_code }}
                            -
                            {{ $account->account_name }}

                            <small class="text-gray-500">

                                ({{ $account->account_type }})

                            </small>

                        </span>


                    </label>


                @endforeach


                </div>


            </div>






            <button
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">

                Save Company

            </button>



        </form>



    </div>






<script>


// Select All

document.getElementById('selectAll')
.addEventListener('change', function(){


    let checkboxes =
    document.querySelectorAll('.account-checkbox');


    checkboxes.forEach(cb=>{

        cb.checked=this.checked;

    });


});





// Business Type Auto Select


document.getElementById('business_type')
.addEventListener('change',function(){


    let type=this.value;


    let checkboxes =
    document.querySelectorAll('.account-item');



    checkboxes.forEach(item=>{


        let industry =
        item.dataset.industry;


        let checkbox =
        item.querySelector('input');



        if(industry=="All" || industry==type)
        {

            checkbox.checked=true;

        }

        else

        {

            checkbox.checked=false;

        }



    });



});



</script>



@endsection