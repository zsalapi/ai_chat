<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    // Az Ollama szerver elérhetősége (alapértelmezetten localhost:11434)
    protected $baseUrl;

    // A szöveggenerálásra (válaszadásra) használt LLM modell, pl. qwen:7b
    protected $genModel;

    // A szövegből számsorozattá (vektorrá) formáló modell (későbbi vektor adatbázis kereséshez)
    protected $embedModel;

    public function __construct()
    {
        // Konfiguráció betöltése a .env fájlból, fallback értékekkel ha nincs megadva
        $this->baseUrl = env('OLLAMA_URL', 'http://localhost:11434');
        //qwen2.5-coder:3b-instruct  - Laptop
        //qwen2.5-coder:7b - Desktop
        $this->genModel = env('OLLAMA_GEN_MODEL', 'qwen2.5-coder:3b-instruct');
        $this->embedModel = env('OLLAMA_EMBED_MODEL', 'nomic-embed-text:latest');
    }

    /**
     * Egy adott szövegből (pl. felhasználó kérdése) Vektort (Embeddings) generál az Ollama segítségével.
     * Ez akkor lesz hasznos, ha rátérünk a pgvector alapú, több ezer adatos GY.I.K. keresésre.
     */
    public function getEmbedding(string $text): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/embeddings", [
                'model' => $this->embedModel,
                'prompt' => $text,
            ]);

            return $response->json()['embedding'] ?? [];
        } catch (\Exception $e) {
            Log::error("Ollama Embedding Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Intelligens választ generál a felhasználó kérdésére (Prompt) a FAQ Adatbázis (Context)
     * és a korábbi chat előzmények (History) figyelembevételével. Ez az ún. RAG módszer.
     *
     * @param string $prompt A látogató aktuális kérdése
     * @param string $context A betöltött GY.I.K. szabályok
     * @param array $history Az utolsó néhány elküldött üzenet a kontextus megtartásához
     */
    public function generateResponse(string $prompt, string $context, array $history = [])
    {
        // 1. A korábbi beszélgetés formázása az LLM számára, hogy emlékezzen "miről is volt szó eddig".
        $historyContext = "";
        if (!empty($history)) {
            $historyContext = "Conversation history:\n";
            foreach ($history as $msg) {
                $historyContext .= ($msg['type'] === 'bot' ? "AI: " : "User: ") . $msg['content'] . "\n";
            }
            $historyContext .= "\n";
        }

        // 2. Az LLM "agymosása" (System Prompt). Itt adjuk meg a "személyiségét" és a feladatát angolul,
        // hogy megfelelően paraméterezve és kontextusban adja vissza a választ.
        $systemPrompt = "You are a smart, helpful assistant for EventPro. \n" .
                        "A database of Frequently Asked Questions (Context) is provided below. \n" .
                        "You MUST ONLY answer questions that are related to EventPro or can be answered using the Context provided. \n" .
                        "If the user asks a question that is completely unrelated to EventPro or the Context (for example: coding questions, general knowledge, or other generic AI queries), you MUST politely decline to answer and state that you can only assist with EventPro related matters. \n" .
                        "IMPORTANT: You MUST always communicate in English. Even if the user asks a question in another language, you must answer in English.\n\n" .
                        "Context:\n" . $context . "\n\n" . $historyContext;

        try {
            // 3. HTTP Kérés küldése az Ollama API-nak. Átadjuk a modellt és az összeállított komplex szöveget.
            $response = Http::post("{$this->baseUrl}/api/generate", [
                'model' => $this->genModel,
                'prompt' => $systemPrompt . "User Question: " . $prompt,
                'stream' => false, // false = megvárjuk a teljes választ, nem darabokban kérjük vissza
            ]);

            // 4. Ha az API 200 OK-val tért vissza, kiolvassuk a "response" JSON kulcsot.
            if ($response->successful()) {
                return $response->json()['response'] ?? 'I am sorry, I am unable to answer that right now.';
            }

            Log::error("Ollama Response Error: " . $response->body());
            return 'I am sorry, I am having trouble connecting to my brain.';
        } catch (\Exception $e) {
            Log::error("Ollama Exception: " . $e->getMessage());
            return 'Sajnos hiba történt a válasz generálása közben.';
        }
    }
}
