<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatbotKnowledge;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        $message = trim($request->message);

        // --- Stop words ---
        $stopWords = [
            'who', 'can', 'the', 'a', 'an', 'is', 'are', 'was', 'were',
            'what', 'how', 'does', 'do', 'did', 'i', 'my', 'for', 'to',
            'of', 'in', 'on', 'at', 'by', 'with', 'and', 'or', 'not',
            'that', 'this', 'it', 'be', 'been', 'has', 'have', 'had'
        ];

        // --- Extract and clean keywords ---
        $rawWords = explode(' ', strtolower($message));
        $keywords = array_filter(
            array_map(fn($word) => preg_replace('/[^a-z0-9]/', '', $word), $rawWords),
            fn($word) => strlen($word) > 2 && !in_array($word, $stopWords)
        );

        // --- Score and rank knowledge rows ---
        $knowledge = ChatbotKnowledge::all()
            ->map(function ($k) use ($keywords) {
                $contentLower  = strtolower($k->content);
                $titleLower    = strtolower($k->title);
                $categoryLower = strtolower($k->category);
                $combined      = $contentLower . ' ' . $titleLower . ' ' . $categoryLower;

                $score = 0;
                foreach ($keywords as $word) {
                    // Basic stemming
                    $stem = $word;
                    foreach (['ing', 'ed', 's'] as $suffix) {
                        if (str_ends_with($stem, $suffix) && strlen($stem) - strlen($suffix) > 2) {
                            $stem = substr($stem, 0, strlen($stem) - strlen($suffix));
                            break;
                        }
                    }

                    if (stripos($combined, $stem) !== false) {
                        if (stripos($titleLower, $stem) !== false)         $score += 3;
                        elseif (stripos($categoryLower, $stem) !== false)  $score += 2;
                        else                                                $score += 1;
                    }
                }

                $k->_score = $score;
                return $k;
            })
            ->filter(fn($k) => $k->_score > 0)
            ->sortByDesc('_score')
            ->take(5);

        // --- Build context ---
        $context = '';
        foreach ($knowledge as $k) {
            $context .= "---\n";
            $context .= "Title: {$k->title}\n";
            $context .= "Category: {$k->category}\n";
            $context .= "Content: {$k->content}\n";
        }
        $context .= "---";

        // --- Early return if no knowledge matched ---
        if ($knowledge->isEmpty()) {
            return response()->json([
                'reply' => "I don't have that information in the company knowledge base."
            ]);
        }

        // --- Build prompt ---
        $prompt = "<|system|>\n"
            . "You are FireBot, an internal assistant for FireSecure SARL.\n"
            . "Roles: SSIAP1 = agent, SSIAP2 = supervisor, SSIAP3 = chief.\n\n"
            . "RULES (follow exactly):\n"
            . "1. Answer ONLY using the KNOWLEDGE below.\n"
            . "2. If the answer exists: answer directly in 1-3 sentences. No preamble.\n"
            . "3. If the answer does NOT exist in the knowledge: reply with only this: \"I don't have that information in the company knowledge base.\"\n"
            . "4. Never combine rule 2 and rule 3 in the same response.\n"
            . "5. Reply in the same language as the question.\n"
            . "<|end|>\n"
            . "<|user|>\n"
            . "KNOWLEDGE:\n"
            . $context . "\n\n"
            . "QUESTION: " . $message . "\n"
            . "<|end|>\n"
            . "<|assistant|>\n";

        // --- Call Ollama ---
        $response = Http::timeout(300)->post('http://localhost:11434/api/generate', [
            'model'       => 'phi3',
            'prompt'      => $prompt,
            'stream'      => false,
            'temperature' => 0.1,
            'stop'        => ['<|end|>', '<|user|>', '<|system|>'],
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error'  => 'Ollama request failed',
                'status' => $response->status(),
                'detail' => $response->body(),
            ], 500);
        }

        $reply = trim($response->json()['response'] ?? '');

        if (empty($reply)) {
            return response()->json([
                'reply' => "I don't have that information in the company knowledge base."
            ]);
        }

        return response()->json([
            'reply' => $reply
        ]);
    }
}