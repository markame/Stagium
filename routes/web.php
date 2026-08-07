<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanySearchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDocumentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\StudentCommitmentTermController;
use App\Http\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
});

Route::middleware(['auth', 'coordinator'])->group(function (): void {
    Route::get('/', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students-import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('/students-import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students-import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/student-commitment-terms', [StudentCommitmentTermController::class, 'index'])->name('students.commitment-terms.index');
    Route::post('/student-commitment-terms', [StudentCommitmentTermController::class, 'store'])->name('students.commitment-terms.store');
    Route::delete('/student-commitment-terms/{document}', [StudentCommitmentTermController::class, 'destroy'])->name('students.commitment-terms.destroy');
    Route::get('/student-documents', fn () => redirect()->route('companies.terms.index'));
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::get('/company-documents', [CompanyDocumentController::class, 'index'])->name('companies.documents.index');
    Route::post('/companies/{company}/documents/{type}', [CompanyDocumentController::class, 'store'])->name('companies.documents.store');
    Route::post('/companies/{company}/official-documents', [CompanyDocumentController::class, 'generateOfficial'])->name('companies.documents.generate-official');
    Route::get('/company-documents/{document}/download', [CompanyDocumentController::class, 'download'])->name('companies.documents.download');
    Route::get('/company-documents/{company}/download-all', [CompanyDocumentController::class, 'downloadAll'])->name('companies.documents.download-all');
    Route::delete('/company-documents/{document}', [CompanyDocumentController::class, 'destroy'])->name('companies.documents.destroy');
    Route::get('/company-forwarding-terms', [StudentDocumentController::class, 'index'])->name('companies.terms.index');
    Route::post('/companies/forwarding-terms', [StudentDocumentController::class, 'store'])->name('companies.terms.store');
    Route::get('/companies/forwarding-terms/{document}/download', [StudentDocumentController::class, 'download'])->name('companies.terms.download');
    Route::delete('/companies/forwarding-terms/{document}', [StudentDocumentController::class, 'destroy'])->name('companies.terms.destroy');
    Route::get('/companies/search', CompanySearchController::class)->name('companies.search');
    Route::post('/companies/scan', [CompanySearchController::class, 'scan'])->name('companies.scan');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'student'])->prefix('aluno')->group(function (): void {
    Route::get('/', [StudentPortalController::class, 'index'])->name('student.portal');
    Route::post('/ponto', [StudentPortalController::class, 'mark'])->name('student.time-log.mark');
});
