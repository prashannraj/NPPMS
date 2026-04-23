<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Contract;
use App\Models\Bid;
use App\Models\DocumentTemplate;
use App\Services\NepaliDateService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentGenerationService
{
    protected $nepaliDateService;

    public function __construct(NepaliDateService $nepaliDateService)
    {
        $this->nepaliDateService = $nepaliDateService;
    }

    /**
     * Generate a bid notice document
     */
    public function generateBidNotice(Project $project, array $bidData, string $format = 'pdf'): string
    {
        $data = $this->prepareBidNoticeData($project, $bidData);
        $template = $this->getTemplate('bid_notice', $project->local_body_id);
        $filename = "bid_notice_{$project->project_code}_" . time();

        return $this->generateDocument($template, $data, $filename, $format);
    }

    /**
     * Generate a contract document
     */
    public function generateContract(Contract $contract, string $format = 'pdf'): string
    {
        $data = $this->prepareContractData($contract);
        $template = $this->getTemplate('contract', $contract->project->local_body_id);
        $filename = "contract_{$contract->contract_number}_" . time();

        return $this->generateDocument($template, $data, $filename, $format);
    }

    /**
     * Generate a work completion certificate
     */
    public function generateCompletionCertificate(Project $project, array $completionData, string $format = 'pdf'): string
    {
        $data = $this->prepareCompletionCertificateData($project, $completionData);
        $template = $this->getTemplate('completion_certificate', $project->local_body_id);
        $filename = "completion_certificate_{$project->project_code}_" . time();

        return $this->generateDocument($template, $data, $filename, $format);
    }

    /**
     * Generate a payment certificate
     */
    public function generatePaymentCertificate(Project $project, array $paymentData, string $format = 'pdf'): string
    {
        $data = $this->preparePaymentCertificateData($project, $paymentData);
        $template = $this->getTemplate('payment_certificate', $project->local_body_id);
        $filename = "payment_certificate_{$project->project_code}_{$paymentData['bill_number']}_" . time();

        return $this->generateDocument($template, $data, $filename, $format);
    }

    /**
     * Generate document from template and data
     */
    protected function generateDocument(string $template, array $data, string $filename, string $format): string
    {
        $content = $this->renderTemplate($template, $data);

        if ($format === 'pdf') {
            return $this->generatePdf($content, $filename);
        } else {
            return $this->generateDocx($content, $filename);
        }
    }

    /**
     * Render template with data
     */
    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            } elseif (is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            
            $placeholder = "{{{$key}}}";
            $template = str_replace($placeholder, $value, $template);
        }

        return $template;
    }

    /**
     * Generate PDF document
     */
    protected function generatePdf(string $htmlContent, string $filename): string
    {
        // For now, just save HTML content
        // In production, you would use Dompdf or similar library
        $filepath = "documents/{$filename}.html";
        Storage::disk('public')->put($filepath, $htmlContent);
        
        Log::info("PDF generated: {$filepath}");
        
        return $filepath;
    }

    /**
     * Generate DOCX document
     */
    protected function generateDocx(string $content, string $filename): string
    {
        // For now, just save text content
        // In production, you would use PhpWord library
        $filepath = "documents/{$filename}.txt";
        Storage::disk('public')->put($filepath, $content);
        
        Log::info("DOCX generated: {$filepath}");
        
        return $filepath;
    }

    /**
     * Prepare data for bid notice
     */
    protected function prepareBidNoticeData(Project $project, array $bidData): array
    {
        $todayBs = $this->nepaliDateService->adToBs(now()->format('Y-m-d'));
        $todayAd = now()->format('Y-m-d');

        return [
            'project_name_np' => $project->project_name_np,
            'project_name_en' => $project->project_name_en,
            'project_code' => $project->project_code,
            'local_body_name_np' => $project->localBody->name_np ?? '',
            'local_body_name_en' => $project->localBody->name_en ?? '',
            'ward_number' => $project->ward->ward_number ?? '',
            'estimated_cost_np' => $this->nepaliDateService->convertToNepaliNumerals(number_format($project->estimated_cost, 2)),
            'estimated_cost_en' => number_format($project->estimated_cost, 2),
            'bid_opening_date_bs' => $this->nepaliDateService->formatNepaliDate($bidData['bid_opening_date_bs'] ?? ''),
            'bid_submission_deadline_bs' => $this->nepaliDateService->formatNepaliDate($bidData['bid_submission_deadline_bs'] ?? ''),
            'today_bs' => $this->nepaliDateService->formatNepaliDate($todayBs),
            'today_ad' => date('F j, Y', strtotime($todayAd)),
        ];
    }

    /**
     * Prepare data for contract
     */
    protected function prepareContractData(Contract $contract): array
    {
        $todayBs = $this->nepaliDateService->adToBs(now()->format('Y-m-d'));
        $todayAd = now()->format('Y-m-d');

        return [
            'contract_number' => $contract->contract_number,
            'project_name_np' => $contract->project->project_name_np,
            'contractor_name_np' => $contract->contractor->name_np ?? '',
            'contract_amount_np' => $this->nepaliDateService->convertToNepaliNumerals(number_format($contract->contract_amount, 2)),
            'contract_amount_en' => number_format($contract->contract_amount, 2),
            'start_date_bs' => $this->nepaliDateService->formatNepaliDate($contract->start_date_bs),
            'completion_date_bs' => $this->nepaliDateService->formatNepaliDate($contract->completion_date_bs),
            'today_bs' => $this->nepaliDateService->formatNepaliDate($todayBs),
            'today_ad' => date('F j, Y', strtotime($todayAd)),
        ];
    }

    /**
     * Prepare data for completion certificate
     */
    protected function prepareCompletionCertificateData(Project $project, array $completionData): array
    {
        $todayBs = $this->nepaliDateService->adToBs(now()->format('Y-m-d'));
        $todayAd = now()->format('Y-m-d');

        return [
            'project_name_np' => $project->project_name_np,
            'project_code' => $project->project_code,
            'completion_date_bs' => $this->nepaliDateService->formatNepaliDate($completionData['completion_date_bs'] ?? ''),
            'final_amount_np' => $this->nepaliDateService->convertToNepaliNumerals(number_format($completionData['final_amount'] ?? 0, 2)),
            'final_amount_en' => number_format($completionData['final_amount'] ?? 0, 2),
            'today_bs' => $this->nepaliDateService->formatNepaliDate($todayBs),
            'today_ad' => date('F j, Y', strtotime($todayAd)),
        ];
    }

    /**
     * Prepare data for payment certificate
     */
    protected function preparePaymentCertificateData(Project $project, array $paymentData): array
    {
        $todayBs = $this->nepaliDateService->adToBs(now()->format('Y-m-d'));
        $todayAd = now()->format('Y-m-d');

        return [
            'project_name_np' => $project->project_name_np,
            'project_code' => $project->project_code,
            'bill_number' => $paymentData['bill_number'] ?? '',
            'bill_number_np' => $this->nepaliDateService->convertToNepaliNumerals($paymentData['bill_number'] ?? ''),
            'bill_amount_np' => $this->nepaliDateService->convertToNepaliNumerals(number_format($paymentData['bill_amount'] ?? 0, 2)),
            'bill_amount_en' => number_format($paymentData['bill_amount'] ?? 0, 2),
            'bill_date_bs' => $this->nepaliDateService->formatNepaliDate($paymentData['bill_date_bs'] ?? ''),
            'today_bs' => $this->nepaliDateService->formatNepaliDate($todayBs),
            'today_ad' => date('F j, Y', strtotime($todayAd)),
        ];
    }

    /**
     * Get template for document type and local body
     */
    protected function getTemplate(string $documentType, int $localBodyId): string
    {
        // Try to get custom template for this local body
        $template = DocumentTemplate::where('document_type', $documentType)
            ->where('local_body_id', $localBodyId)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->template_content;
        }

        // Fallback to default template
        return $this->getDefaultTemplate($documentType);
    }

    /**
     * Get default template for document type
     */
    protected function getDefaultTemplate(string $documentType): string
    {
        $templates = [
            'bid_notice' => $this->getDefaultBidNoticeTemplate(),
            'contract' => $this->getDefaultContractTemplate(),
            'completion_certificate' => $this->getDefaultCompletionCertificateTemplate(),
            'payment_certificate' => $this->getDefaultPaymentCertificateTemplate(),
        ];

        return $templates[$documentType] ?? '<h1>Document Template Not Found</h1>';
    }

    /**
     * Get default bid notice template
     */
    protected function getDefaultBidNoticeTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>बोलपत्र सूचना - {{project_name_np}}</title>
</head>
<body>
    <h1>{{local_body_name_np}}</h1>
    <h2>बोलपत्र सूचना</h2>
    <p>वडा नं.: {{ward_number}}</p>
    
    <h3>परियोजना विवरण:</h3>
    <p>परियोजना नाम: {{project_name_np}}</p>
    <p>परियोजना कोड: {{project_code}}</p>
    <p>अनुमानित लागत: रु. {{estimated_cost_np}} ({{estimated_cost_en}})</p>
    
    <h3>बोलपत्र सम्बन्धी मिति:</h3>
    <p>बोलपत्र दाखिला अन्तिम मिति: {{bid_submission_deadline_bs}}</p>
    <p>बोलपत्र खुल्ने मिति: {{bid_opening_date_bs}}</p>
    
    <p>मिति: {{today_bs}} ({{today_ad}})</p>
</body>
</html>
HTML;
    }

    /**
     * Get default contract template
     */
    protected function getDefaultContractTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>सम्झौता - {{contract_number}}</title>
</head>
<body>
    <h1>सम्झौता पत्र</h1>
    <h2>सम्झौता नं.: {{contract_number}}</h2>
    
    <h3>परियोजना विवरण:</h3>
    <p>परियोजना नाम: {{project_name_np}}</p>
    
    <h3>ठेकेदार विवरण:</h3>
    <p>ठेकेदार नाम: {{contractor_name_np}}</p>
    
    <h3>सम्झौता रकम:</h3>
    <p>रु. {{contract_amount_np}} ({{contract_amount_en}})</p>
    
    <h3>सम्झौता अवधि:</h3>
    <p>सुरु मिति: {{start_date_bs}}</p>
    <p>पूर्णता मिति: {{completion_date_bs}}</p>
    
    <p>मिति: {{today_bs}} ({{today_ad}})</p>
</body>
</html>
HTML;
    }

    /**
     * Get default completion certificate template
     */
    protected function getDefaultCompletionCertificateTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>कार्य पूर्णता प्रमाणपत्र - {{project_code}}</title>
</head>
<body>
    <h1>कार्य पूर्णता प्रमाणपत्र</h1>
    
    <h3>परियोजना विवरण:</h3>
    <p>परियोजना नाम: {{project_name_np}}</p>
    <p>परियोजना कोड: {{project_code}}</p>
    
    <h3>पूर्णता विवरण:</h3>
    <p>पूर्णता मिति: {{completion_date_bs}}</p>
    <p>अन्तिम रकम: रु. {{final_amount_np}} ({{final_amount_en}})</p>
    
    <p>यस प्रमाणपत्र द्वारा प्रमाणित गरिन्छ कि उक्त परियोजना पूर्ण रूपमा सम्पन्न भएको छ।</p>
    
    <p>मिति: {{today_bs}} ({{today_ad}})</p>
</body>
</html>
HTML;
    }

    /**
     * Get default payment certificate template
     */
    protected function getDefaultPaymentCertificateTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>भुक्तानी प्रमाणपत्र - {{bill_number}}</title>
</head>
<body>
    <h1>भुक्तानी प्रमाणपत्र</h1>
    
    <h3>परियोजना विवरण:</h3>
    <p>परियोजना नाम: {{project_name_np}}</p>
    <p>परियोजना कोड: {{project_code}}</p>
    
    <h3>बिल विवरण:</h3>
    <p>बिल नं.: {{bill_number_np}}</p>
    <p>बिल मिति: {{bill_date_bs}}</p>
    <p>बिल रकम: रु. {{bill_amount_np}} ({{bill_amount_en}})</p>
    
    <p>यस प्रमाणपत्र द्वारा प्रमाणित गरिन्छ कि उक्त बिलको भुक्तानी गर्न उपयुक्त छ।</p>
    
    <p>मिति: {{today_bs}} ({{today_ad}})</p>
</body>
</html>
HTML;
    }

    /**
     * Get procurement method name in Nepali
     */
    protected function getProcurementMethodNameNp(string $method): string
    {
        $methods = [
            'open_bidding' => 'खुला बोलपत्र',
            'limited_bidding' => 'सीमित बोलपत्र',
            'direct_procurement' => 'प्रत्यक्ष खरिद',
            'request_for_proposal' => 'प्रस्ताव आह्वान',
        ];

        return $methods[$method] ?? $method;
    }
}