<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ResumeAnalyzer;
use Smalot\PdfParser\Parser;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;

class ResumeAnalyzerController extends Controller
{
    public function index(){
        return view('resume.index');
    }

    public function analyze(Request $request){
        $request->validate([
            'resume' => ['required','file','mimes:pdf','max:5120'],
            'job_description' => ['nullable','string','max:10000']
        ]);

        $file = $request->file('resume');
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getRealPath());
        $resumeText = $this->cleanPdfText($pdf->getText());

        if (strlen($resumeText) < 100) {
            return back()->withErrors([
                'resume' => 'Could not extract enough text from this PDF. It may be scanned or image-based.',
            ]);
        }

        $resumeText = str($resumeText)->limit(5000)->toString();
        $jobDescription = $request->input('job_description')
            ?: 'Not provided. Analyze the resume generally.';
        $prompt = <<<PROMPT
        Analyze the following resume carefully.

        RESUME:
        {$resumeText}

        JOB DESCRIPTION:
        {$jobDescription}

        Requirements:
        - Return a score from 0 to 100.
        - Evaluate ATS compatibility.
        - Identify strengths, weaknesses, and missing keywords.
        - Give practical, specific improvement suggestions.
        - Rewrite the professional summary using only facts supported by the resume.
        - Recommend suitable job roles.
        - Do not invent qualifications, skills, or work experience.
        - Follow the structured output schema exactly.
        PROMPT;

        $analysis = (new ResumeAnalyzer)->prompt($prompt);

        return view('resume.result', [
            'analysis' => $analysis,
        ]);
    }

    private function cleanPdfText(string $text): string
    {
        // Remove null bytes and control characters
        $text = str_replace("\0", '', $text);

        // Force valid UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove invalid UTF-8 sequences
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

        // Remove non-printable characters except line breaks and tabs
        $text = preg_replace('/[^\P{C}\n\r\t]+/u', '', $text);

        // Normalize whitespace
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
