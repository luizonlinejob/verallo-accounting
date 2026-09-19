<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * 📋 List all courses
     */
    public function index()
    {
        $courses = Course::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ], 200);
    }

    /**
     * ➕ Create new course (Superadmin only)
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can manage courses.',
            ], 403);
        }

        $request->validate([
            'name'        => 'required|string|max:255|unique:courses,name',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $course = Course::create([
                'name'        => $request->name,
                'code'        => $request->code,
                'description' => $request->description,
                'is_active'   => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course added successfully!',
                'course'  => $course,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add course: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✏️ Update course (Superadmin only)
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can manage courses.',
            ], 403);
        }

        $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('courses', 'name')->ignore($id)],
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        try {
            $course = Course::findOrFail($id);

            $course->update([
                'name'        => $request->name,
                'code'        => $request->code,
                'description' => $request->description,
                'is_active'   => $request->is_active ?? $course->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully!',
                'course'  => $course,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Delete course (Superadmin only)
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can manage courses.',
            ], 403);
        }

        try {
            $course = Course::findOrFail($id);
            $course->delete();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course: ' . $e->getMessage(),
            ], 500);
        }
    }
}