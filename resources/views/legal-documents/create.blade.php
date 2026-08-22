@extends('layouts.app')

@section('title', 'Upload Legal Document')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3">
                <a href="{{ route('legal-documents.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">Upload Legal Document</h1>
                    <p class="mt-2 text-gray-600">Add a new legal document to your gallery</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md p-8">
            <form action="{{ route('legal-documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <h4 class="font-semibold mb-2">Please fix the following errors:</h4>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Basic Information Section -->
                <div class="border-b pb-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Basic Information
                    </h2>

                    <!-- Title -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Document Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}"
                               placeholder="e.g., Business License 2024"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-500 @enderror"
                               required>
                        @error('title')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Document Number -->
                    <div class="mb-6">
                        <label for="document_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Document Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="document_number" name="document_number" value="{{ old('document_number') }}"
                               placeholder="e.g., DOC-2024-001"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('document_number') border-red-500 @enderror"
                               required>
                        @error('document_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select id="category" name="category"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category') border-red-500 @enderror"
                                    required>
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company (Optional) -->
                        <div>
                            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Company
                            </label>
                            <select id="company_id" name="company_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Select Company (Optional) --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Add any additional details about this document..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        <p class="text-gray-500 text-sm mt-1">Max 1000 characters</p>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dates Section -->
                <div class="border-b pb-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-calendar-alt mr-2"></i> Validity Dates
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Issue Date -->
                        <div>
                            <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Issue Date
                            </label>
                            <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('issue_date') border-red-500 @enderror">
                            @error('issue_date')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Expiry Date
                            </label>
                            <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('expiry_date') border-red-500 @enderror">
                            <p class="text-gray-500 text-sm mt-1">Leave blank if document doesn't expire</p>
                            @error('expiry_date')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- File Upload Section -->
                <div class="border-b pb-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-file-upload mr-2"></i> Upload Document
                    </h2>

                    <!-- Drag and Drop Area -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition-colors"
                         id="dropZone">
                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                        <p class="text-xl font-semibold text-gray-700 mb-2">Drag and drop your file here</p>
                        <p class="text-gray-600 mb-4">or</p>
                        <label for="file" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg cursor-pointer">
                            Browse Files
                        </label>
                        <input type="file" id="file" name="file" class="hidden"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls,.zip"
                               required @error('file') @enderror>
                        <p class="text-gray-500 text-sm mt-4">
                            <strong>Allowed formats:</strong> PDF, DOC, DOCX, JPG, PNG, XLSX, XLS, ZIP
                            <br>
                            <strong>Max file size:</strong> 10 MB
                        </p>
                    </div>

                    <!-- File Preview -->
                    <div id="filePreview" class="mt-4 hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                                <div>
                                    <p class="font-semibold text-gray-900" id="fileName"></p>
                                    <p class="text-sm text-gray-600" id="fileSize"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('file')
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4 justify-end">
                    <a href="{{ route('legal-documents.index') }}"
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                        <i class="fas fa-upload mr-2"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Drag and drop functionality
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('filePreview');

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFilePreview();
        }
    });

    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', updateFilePreview);

    function updateFilePreview() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = `Size: ${formatFileSize(file.size)}`;
            filePreview.classList.remove('hidden');
        } else {
            filePreview.classList.add('hidden');
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush
@endsection
