<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;
use Laravel\Ai\Attributes\Timeout;

#[Timeout(180)]
class ResumeAnalyzer implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public function provider(): string
    {
        return 'ollama';
    }

    public function model(): string
    {
        return 'llama3.2:latest';
    }

    
    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT
            You are an expert technical recruiter and resume reviewer.
            Analyze resumes honestly and practically
            Return:
            -overall score out of 100
            -short summary
            -strengths
            -missing keywords
            -improvement suggestions
            -better rewritten professional summary
            -suitable job roles
            -ATS score
            -ATS issues

            Do not invent experience. If information is missing, say so.  
        
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'overall_score' => $schema->integer()->required(),
            'ats_score' => $schema->integer()->required(),
            'summary' => $schema->string()->required(),
            
            'strengths' => $schema->array()->items($schema->string())->required(),
            'weaknesses' => $schema->array()->items($schema->string())->required(),
            'missing_keywords' => $schema->array()->items($schema->string())->required(),
            'suggestions' => $schema->array()->items($schema->string())->required(),
            'ats_issues' => $schema->array()->items($schema->string())->required(),
            'recommended_roles' => $schema->array()->items($schema->string())->required(),
            'rewritten_summary' => $schema->string()->required()
        ];
    }
}
