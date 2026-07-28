@extends('layouts.app')

@section('title', $legalDocument->title)

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('legal-documents.index') }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900">{{ $legalDocument->title }}</h1>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full {{ $legalDocument->getCategoryBadgeClass() }}">
                                {{ $legalDocument->category }}
                            </span>
                            @if($legalDocument->isExpired())
                                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i> Expired
                                </span>
                            @elseif($legalDocument->isExpiringsoon())
                                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Expiring Soon
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Active
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <a href="{{ route('legal-documents.download', $legalDocument) }}"
                       class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fas fa-download mr-2"></i> Download
                    </a>
                    <a href="{{ route('legal-documents.edit', $legalDocument) }}"
                       class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                    <button onclick="printDocument()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.style.display='none'" class="text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Document Preview -->
                <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
                    <div class="bg-gray-100 h-96 flex items-center justify-center">
                        @if($previewUrl && in_array($legalDocument->file_type, ['jpg', 'jpeg', 'png']))
                            <img src="{{ $previewUrl }}" alt="{{ $legalDocument->title }}" class="w-full h-full object-contain">
                        @elseif($previewUrl && $legalDocument->file_type === 'pdf')
                            <div class="text-center">
                                <i class="fas fa-file-pdf text-red-500 text-8xl mb-4"></i>
                                <p class="text-gray-600">PDF Preview</p>
                                <a href="{{ $previewUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                    <i class="fas fa-external-link-alt mr-2"></i> Open in new tab
                                </a>
                            </div>
                        @else
                            <div class="text-center">
                                <i class="fas {{ $legalDocument->getFileIcon() }} text-8xl mb-4"></i>
                                <p class="text-gray-600 text-lg">{{ strtoupper($legalDocument->file_type) }} File</p>
                                <p class="text-gray-500 text-sm mt-2">File preview not available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Description -->
                @if($legalDocument->description)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-align-left mr-2"></i> Description
                        </h2>
                        <p class="text-gray-700 leading-relaxed">{{ $legalDocument->description }}</p>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- File Details -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-file mr-2"></i> File Details
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600">File Name</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->file_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">File Size</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->getHumanReadableFileSize() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">File Type</p>
                            <p class="font-semibold text-gray-900">{{ strtoupper($legalDocument->file_type) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Uploaded</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->created_at->format('M d, Y \a\t H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Document Details
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600">Document Number</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->document_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Category</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->category }}</p>
                        </div>
                        @if($legalDocument->company)
                            <div>
                                <p class="text-sm text-gray-600">Company</p>
                                <p class="font-semibold text-gray-900">{{ $legalDocument->company->name }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Validity Period -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-calendar-alt mr-2"></i> Validity Period
                    </h3>
                    <div class="space-y-4">
                        @if($legalDocument->issue_date)
                            <div>
                                <p class="text-sm text-gray-600">Issue Date</p>
                                <p class="font-semibold text-gray-900">{{ $legalDocument->issue_date->format('M d, Y') }}</p>
                            </div>
                        @endif

                        @if($legalDocument->expiry_date)
                            <div>
                                <p class="text-sm text-gray-600">Expiry Date</p>
                                <p class="font-semibold text-gray-900">
                                    {{ $legalDocument->expiry_date->format('M d, Y') }}
                                </p>
                                <div class="mt-2 p-2 bg-gray-50 rounded">
                                    @if($legalDocument->isExpired())
                                        <p class="text-red-600 text-sm font-semibold">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Expired {{ $legalDocument->expiry_date->diffForHumans() }}
                                        </p>
                                    @else
                                        <p class="text-gray-700 text-sm">
                                            <i class="fas fa-hourglass-end mr-1"></i>
                                            Expires in {{ now()->diffInDays($legalDocument->expiry_date) }} days
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div>
                                <p class="text-sm text-gray-600">Expiry Date</p>
                                <p class="font-semibold text-gray-900 text-green-600">
                                    <i class="fas fa-infinity mr-1"></i> No expiry
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Review Status -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-check-double mr-2"></i> Review Status
                    </h3>
                    @if($legalDocument->last_reviewed_at)
                        <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                            <p class="text-sm font-semibold text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Last reviewed
                            </p>
                            <p class="text-sm text-gray-700 mt-1">
                                {{ $legalDocument->last_reviewed_at->format('M d, Y') }}
                            </p>
                            @if($legalDocument->reviewedBy)
                                <p class="text-sm text-gray-600">by {{ $legalDocument->reviewedBy->name }}</p>
                            @endif
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                            <p class="text-sm font-semibold text-yellow-800">
                                <i class="fas fa-exclamation-circle mr-1"></i> Not yet reviewed
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('legal-documents.mark-as-reviewed', $legalDocument) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                            <i class="fas fa-check mr-2"></i> Mark as Reviewed
                        </button>
                    </form>
                </div>

                <!-- Meta Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-history mr-2"></i> History
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-600">Created</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Last Updated</p>
                            <p class="font-semibold text-gray-900">{{ $legalDocument->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                        @if($legalDocument->uploadedBy)
                            <div>
                                <p class="text-gray-600">Uploaded by</p>
                                <p class="font-semibold text-gray-900">{{ $legalDocument->uploadedBy->name }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function printDocument() {
        const previewUrl = @json($previewUrl);
        if (previewUrl && @json($legalDocument->file_type) === 'pdf') {
            window.open(previewUrl, '_blank');
        } else {
            alert('Print functionality is limited for this file type. Please download and print manually.');
        }
    }
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush
@endsection
