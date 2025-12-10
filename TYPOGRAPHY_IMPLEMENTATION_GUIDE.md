# Typography System Implementation Guide - Backend

## Overview

This guide explains how to implement and maintain the Unified Typography System in the Coopvest Africa backend (PHP/Laravel). While the backend doesn't directly render typography, it plays a crucial role in:

1. **API Documentation** - Consistent formatting and structure
2. **Email Templates** - HTML emails with proper typography
3. **PDF Generation** - Reports and documents with typography
4. **Response Formatting** - Structured data for frontend consumption

---

## 1. API Documentation Typography

### Standard Response Format

All API responses should follow this structure with consistent typography:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'status' => 'success',
            'message' => 'Operation completed successfully',
            'data' => $this->resource,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0.0',
            ],
        ];
    }
}
```

### Documentation Standards

When documenting API endpoints, use this typography hierarchy:

```markdown
# API Documentation

## Display Large (Hero Title)
# Investment Loans API

## Headline Large (Section)
## Endpoints

## Headline Medium (Subsection)
### GET /api/loans

## Title Medium (Field Headers)
#### Request Parameters

## Body Medium (Descriptions)
The following parameters are required for the loan request...

## Label Medium (Field Names)
- `loan_type`: The type of loan being requested
- `amount`: The loan amount in currency units
```

---

## 2. Email Template Typography

### HTML Email Structure

Create email templates with proper typography hierarchy:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoanApplicationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public $loan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Loan Application Received',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loan-application',
            with: [
                'user' => $this->user,
                'loan' => $this->loan,
            ],
        );
    }
}
```

### Email Template HTML

```html
<!-- resources/views/emails/loan-application.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Font Families */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1A1A1A;
            line-height: 1.5;
        }

        h1 {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.3;
            margin: 0 0 24px 0;
        }

        h2 {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
            margin: 0 0 16px 0;
        }

        .body-large {
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.5px;
            margin: 0 0 16px 0;
        }

        .body-medium {
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.25px;
            margin: 0 0 12px 0;
        }

        .label-large {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.1px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1E88E5;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.1px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 32px;
        }

        .section {
            margin-bottom: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Display Large equivalent -->
        <h1>Welcome to Coopvest Africa</h1>

        <!-- Headline Large -->
        <h2>Loan Application Received</h2>

        <!-- Body Large -->
        <p class="body-large">
            Dear {{ $user->first_name }},
        </p>

        <!-- Body Medium -->
        <p class="body-medium">
            Thank you for submitting your loan application. We have received your request and our team is reviewing it.
        </p>

        <div class="section">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px;">Application Details</h3>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;">
                        <span class="label-large">Loan Type:</span>
                    </td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0; text-align: right;">
                        <span class="body-medium">{{ $loan->type }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;">
                        <span class="label-large">Amount:</span>
                    </td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0; text-align: right;">
                        <span class="body-medium">{{ $loan->formatted_amount }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;">
                        <span class="label-large">Status:</span>
                    </td>
                    <td style="padding: 8px 0; text-align: right;">
                        <span class="body-medium">{{ $loan->status }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <a href="{{ $applicationUrl }}" class="button">View Application</a>
        </div>

        <!-- Body Small for footer -->
        <p style="font-size: 12px; font-weight: 400; line-height: 1.5; letter-spacing: 0.4px; color: #999999; margin-top: 32px;">
            If you have any questions, please contact our support team at support@coopvest.com
        </p>
    </div>
</body>
</html>
```

---

## 3. PDF Generation Typography

### Using DomPDF

```php
<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class LoanReportService
{
    /**
     * Generate a loan report PDF with proper typography
     */
    public function generateReport($loan)
    {
        $data = [
            'loan' => $loan,
            'typography' => $this->getTypographyStyles(),
        ];

        $pdf = Pdf::loadView('reports.loan-report', $data);
        
        return $pdf->download('loan-report-' . $loan->id . '.pdf');
    }

    /**
     * Get typography styles for PDF
     */
    private function getTypographyStyles()
    {
        return [
            'displayLarge' => [
                'fontSize' => 57,
                'fontWeight' => 700,
                'lineHeight' => 1.2,
                'letterSpacing' => -0.25,
            ],
            'headlineLarge' => [
                'fontSize' => 32,
                'fontWeight' => 700,
                'lineHeight' => 1.3,
                'letterSpacing' => 0,
            ],
            'bodyLarge' => [
                'fontSize' => 16,
                'fontWeight' => 400,
                'lineHeight' => 1.5,
                'letterSpacing' => 0.5,
            ],
            'bodyMedium' => [
                'fontSize' => 14,
                'fontWeight' => 400,
                'lineHeight' => 1.5,
                'letterSpacing' => 0.25,
            ],
            'labelLarge' => [
                'fontSize' => 14,
                'fontWeight' => 500,
                'lineHeight' => 1.4,
                'letterSpacing' => 0.1,
            ],
        ];
    }
}
```

### PDF Template

```blade
<!-- resources/views/reports/loan-report.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            color: #1A1A1A;
            margin: 0;
            padding: 20px;
        }

        .display-large {
            font-family: 'Poppins', sans-serif;
            font-size: 57px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.25px;
            margin-bottom: 32px;
        }

        .headline-large {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 16px;
        }

        .body-large {
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .body-medium {
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.25px;
            margin-bottom: 12px;
        }

        .label-large {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #E0E0E0;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #E0E0E0;
        }
    </style>
</head>
<body>
    <div class="display-large">Loan Report</div>

    <div class="headline-large">Application Details</div>

    <table>
        <thead>
            <tr>
                <th class="label-large">Field</th>
                <th class="label-large">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="body-medium">Loan Type</td>
                <td class="body-medium">{{ $loan->type }}</td>
            </tr>
            <tr>
                <td class="body-medium">Amount</td>
                <td class="body-medium">{{ $loan->formatted_amount }}</td>
            </tr>
            <tr>
                <td class="body-medium">Status</td>
                <td class="body-medium">{{ $loan->status }}</td>
            </tr>
        </tbody>
    </table>

    <div class="body-large">
        {{ $loan->description }}
    </div>
</body>
</html>
```

---

## 4. API Response Formatting

### Consistent Response Structure

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class LoanController extends Controller
{
    /**
     * Get all loans with consistent typography in response
     */
    public function index(): JsonResponse
    {
        $loans = Loan::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Loans retrieved successfully',
            'data' => [
                'loans' => $loans,
                'count' => $loans->count(),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0.0',
            ],
        ]);
    }

    /**
     * Create a new loan with validation messages
     */
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = Loan::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Loan created successfully',
            'data' => $loan,
        ], 201);
    }

    /**
     * Handle validation errors with consistent formatting
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 422));
    }
}
```

---

## 5. Documentation Standards

### README Typography

```markdown
# Coopvest Africa Backend API

## Display Large (Main Title)
# Investment Platform Backend

## Headline Large (Major Sections)
## Getting Started

## Headline Medium (Subsections)
### Installation

## Title Medium (Instruction Headers)
#### Prerequisites

## Body Large (Main Content)
This backend provides RESTful APIs for managing loans, investments, and user accounts.

## Body Medium (Supporting Content)
The API uses JSON for request and response bodies.

## Label Large (Code Labels)
**Endpoint:** `POST /api/loans`
```

---

## 6. Error Messages Typography

### Consistent Error Formatting

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->message,
            'error_code' => $this->code,
            'details' => [
                'description' => 'A detailed description of the error',
                'suggestion' => 'How to resolve this error',
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->id(),
            ],
        ], $this->getStatusCode());
    }

    private function getStatusCode(): int
    {
        return match ($this->code) {
            'VALIDATION_ERROR' => 422,
            'NOT_FOUND' => 404,
            'UNAUTHORIZED' => 401,
            'FORBIDDEN' => 403,
            default => 500,
        };
    }
}
```

---

## 7. Implementation Checklist

- [ ] Update all API documentation with typography hierarchy
- [ ] Create email templates with proper typography styles
- [ ] Implement PDF generation with typography
- [ ] Standardize API response formatting
- [ ] Update error messages with consistent structure
- [ ] Document typography standards in README
- [ ] Create typography style guide for team
- [ ] Test typography rendering in emails and PDFs
- [ ] Verify font loading in all templates
- [ ] Update API documentation with examples

---

## 8. Font Loading

### Google Fonts Integration

Add to your email and PDF templates:

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
```

### Fallback Fonts

Always include fallback fonts in CSS:

```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
font-family: '"JetBrains Mono"', 'Courier New', monospace;
```

---

## 9. Testing Typography

### Email Template Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Mail\LoanApplicationNotification;
use Illuminate\Support\Facades\Mail;

class EmailTypographyTest extends TestCase
{
    public function test_loan_notification_email_has_correct_typography()
    {
        Mail::fake();

        // Send email
        Mail::send(new LoanApplicationNotification($user, $loan));

        // Assert email was sent
        Mail::assertSent(LoanApplicationNotification::class);
    }
}
```

---

## 10. Maintenance

### Updating Typography

When updating typography system:

1. Update this guide
2. Update email templates
3. Update PDF templates
4. Update API documentation
5. Test all templates
6. Commit changes to all repositories

---

## References

- Main Typography System: `UNIFIED_TYPOGRAPHY_SYSTEM.md`
- Flutter Implementation: `Coopvest_Africa/lib/core/constants/text_styles.dart`
- React Implementation: `coopvest_africa_website/client/src/lib/typography.ts`
