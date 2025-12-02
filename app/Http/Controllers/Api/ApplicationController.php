<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

class ApplicationController extends Controller
{
    /**
     * Get categories for a specific application.
     */
    public function getCategories($id)
    {
        try {
            Log::info('Loading categories for application', ['app_id' => $id]);
            
            $application = Aplikasi::find($id);
            
            if (!$application) {
                Log::warning('Application not found', ['app_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found',
                    'categories' => [],
                ], 404);
            }

            $categories = KategoriMasalah::where('aplikasi_id', $id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'description'])
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                    ];
                });

            Log::info('Categories loaded successfully', [
                'app_id' => $id,
                'count' => $categories->count()
            ]);

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading categories for application ' . $id, [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading categories: ' . $e->getMessage(),
                'categories' => [],
            ], 500);
        }
    }

    /**
     * Get application details.
     */
    public function show($id)
    {
        try {
            $application = Aplikasi::with(['kategoriMasalahs' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'application' => [
                    'id' => $application->id,
                    'code' => $application->code,
                    'name' => $application->name,
                    'description' => $application->description,
                    'category' => $application->category,
                    'status' => $application->status,
                    'categories' => $application->kategoriMasalahs->map(function ($cat) {
                        return [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'description' => $cat->description,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }
    }
}
