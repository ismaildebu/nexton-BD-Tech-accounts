@extends('layouts.app')

@section('title', 'Legal Document Gallery')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">Legal Document Gallery</h1>
                    <p class="mt-2 text-gray-600">Manage and organize all your legal documents</p>
                </div>
                <a href="{{ route('legal-documents.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md">
                    <i class="fas fa-plus mr-2"></i> Upload Document
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Documents -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium">Total Documents</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $report['total'] }}</p>
                    </div>
                    <div class="text-blue-500 text-3xl">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Active Documents -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium">Active</p>
                        <p class="text-2xl font-bold text-green-600">{{ $report['active'] }}</p>
                    </div>
                    <div class="text-green-500 text-3xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium">Expiring Soon</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $report['expiring_soon'] }}</p>
                    </div>
                    <div class="text-yellow-500 text-3xl">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Expired Documents -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium">Expired</p>
                        <p class="text-2xl font-bold text-red-600">{{ $report['expired'] }}</p>
                    </div>
                    <div class="text-red-500 text-3xl">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form action="{{ route('legal-documents.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Title or document number..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected($category === request('category'))>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Company Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                        <select name="company_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected($company->id == request('company_id'))>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="expiring" @selected(request('status') === 'expiring')>Expiring Soon</option>
                            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </div>

                <!-- Clear Filters -->
                @if(request()->anyFilled(['search', 'category', 'company_id', 'status']))
                    <div class="text-right">
                        <a href="{{ route('legal-documents.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-times mr-1"></i> Clear Filters
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Messages -->
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.style.display='none'" class="text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center">
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.style.display='none'" class="text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($documents as $document)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
                    <!-- Document Preview/Icon -->
                    <div class="bg-gray-100 h-40 flex items-center justify-center relative overflow-hidden">
                        @if(in_array($document->file_type, ['jpg', 'jpeg', 'png']))
                            <img src="{{ $document->getFileUrl() }}" alt="{{ $document->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-center">
                                <i class="fas {{ $document->getFileIcon() }} text-5xl mb-2"></i>
                                <p class="text-sm text-gray-600 uppercase">{{ $document->file_type }}</p>
                            </div>
                        @endif

                        <!-- Status Badge -->
                        <div class="absolute top-2 right-2">
                            @if($document->isExpired())
                                <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">
                                    Expired
                                </span>
                            @elseif($document->isExpiringsoon())
                                <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">
                                    Expiring
                                </span>
                            @else
                                <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">
                                    Active
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Document Info -->
                    <div class="p-4">
                        <!-- Title and Category -->
                        <div class="mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $document->title }}</h3>
                            <span class="inline-block mt-2 px-2 py-1 text-xs font-semibold rounded-full {{ $document->getCategoryBadgeClass() }}">
                                {{ $document->category }}
                            </span>
                        </div>

                        <!-- Document Details -->
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <p><strong>Doc #:</strong> {{ $document->document_number }}</p>
                            <p><strong>File Size:</strong> {{ $document->getHumanReadableFileSize() }}</p>
                            @if($document->expiry_date)
                                <p>
                                    <strong>Expires:</strong>
                                    <span class="{{ $document->isExpired() ? 'text-red-600 font-semibold' : '' }}">
                                        {{ $document->expiry_date->format('M d, Y') }}
                                    </span>
                                </p>
                            @endif
                            <p><strong>Uploaded:</strong> {{ $document->created_at->format('M d, Y') }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('legal-documents.show', $document) }}"
                               class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-3 rounded text-center text-sm">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            <a href="{{ route('legal-documents.download', $document) }}"
                               class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-3 rounded text-center text-sm">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                            <a href="{{ route('legal-documents.edit', $document) }}"
                               class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-3 rounded text-center text-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <form action="{{ route('legal-documents.destroy', $document) }}" method="POST" class="flex-1"
                                  onsubmit="return confirm('Are you sure you want to delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-3 rounded text-sm">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-white rounded-lg shadow p-12 text-center">
                        <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Documents Found</h3>
                        <p class="text-gray-600 mb-6">No legal documents match your search criteria.</p>
                        <a href="{{ route('legal-documents.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                            <i class="fas fa-plus mr-2"></i> Upload First Document
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($documents->total() > 0)
            <div class="mt-8">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush
