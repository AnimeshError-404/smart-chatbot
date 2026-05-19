<?php

namespace Ridgeben\SmartChatbot\Services;

class ChatbotEngine
{
    public function __construct(
        protected MemoryService $memory,
        protected SourceSearchService $sourceSearch,
        protected ContextBuilder $contextBuilder,
        protected OllamaClient $ollama,
    ) {
    }

    public function answer(string $question): string
    {
        $question = trim($question);

        if ($this->memory->isResetRequest($question)) {
            $this->memory->clear();

            return 'Chat memory has been cleared. You can now start a new conversation.';
        }

        $results = collect();

        if ($this->memory->hasPronounReference($question)) {
            $lastEntity = $this->memory->lastEntity();

            if ($lastEntity) {
                $results = $this->sourceSearch->findFromMemory($lastEntity);
            }
        }

        if ($results->isEmpty()) {
            $results = $this->sourceSearch->search($question);
        }

        if ($results->isEmpty() && $this->memory->looksLikeFollowUp($question)) {
            $lastEntity = $this->memory->lastEntity();

            if ($lastEntity) {
                $results = $this->sourceSearch->findFromMemory($lastEntity);
            }
        }

        if ($results->isNotEmpty()) {
            $firstResult = $results->first();

            if ($firstResult) {
                $this->memory->rememberEntity($firstResult);
            }

            $context = $this->contextBuilder->build($results);

            $prompt = $this->buildDatabasePrompt($question, $context);

            $answer = $this->ollama->ask($prompt);

            $this->memory->save($question, $answer);

            return $answer;
        }

        $prompt = $this->buildNoDatabasePrompt($question);

        $answer = $this->ollama->ask($prompt);

        $this->memory->save($question, $answer);

        return $answer;
    }

    private function buildDatabasePrompt(string $question, string $context): string
    {
        $history = $this->memory->historyText();

        $assistantName = config('smart-chatbot.website.assistant_name', 'Website Assistant');
        $websiteName = config('smart-chatbot.website.name', 'the website');

        return "
You are {$assistantName}, the official assistant of {$websiteName}.

Your job is to answer the user using the website information provided below.

Conversation memory:
{$history}

Current website information:
{$context}

User question:
{$question}

Rules:
1. Use Current website information as the main truth.
2. Answer by paraphrasing the provided information naturally.
3. Do not simply copy the text word-for-word.
4. If the user asks a follow-up question using it, this, that, or same one, use the Current website information as the topic.
5. If the question asks for a count, give the exact count from Current website information.
6. If the question asks for names, list only the names from Current website information.
7. Do not invent project names, product names, status, stock, prices, locations, dates, apartment sizes, phone numbers, payment plans, availability, delivery details, or legal information.
8. If a specific detail is missing, say that the exact detail is not available right now.
9. Do not start every answer with Hello. Only greet the user if the current user question is a greeting.
10. Keep the answer professional, friendly, and easy to understand.
11. Do not mention database, SQL, Laravel, prompt, context, or technical terms.
";
    }

    private function buildNoDatabasePrompt(string $question): string
    {
        $history = $this->memory->historyText();

        $assistantName = config('smart-chatbot.website.assistant_name', 'Website Assistant');
        $websiteName = config('smart-chatbot.website.name', 'the website');
        $businessType = config('smart-chatbot.website.business_type', 'business');
        $generalKnowledge = config('smart-chatbot.general_knowledge', '');

        return "
You are {$assistantName}, the official assistant of {$websiteName}, a {$businessType}.

Conversation memory:
{$history}

General website knowledge:
{$generalKnowledge}

User question:
{$question}

Rules:
1. If the user greets you, greet them warmly and ask how you can help.
2. If the user asks small talk, answer briefly and politely.
3. If the user asks about website-specific facts but no information is provided, do not invent anything.
4. If exact information is not available in the provided information, say that you do not have that exact information right now.
5. Guide the user to ask about available information or contact the team.
6. Do not mention database, SQL, Laravel, prompt, context, or technical terms.
7. Keep the answer professional, natural, and helpful.
";
    }
}