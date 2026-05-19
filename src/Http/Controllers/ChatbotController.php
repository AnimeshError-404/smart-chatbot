<?php

namespace Ridgeben\SmartChatbot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ridgeben\SmartChatbot\Services\ChatbotEngine;

class ChatbotController extends Controller
{
    public function ask(Request $request, ChatbotEngine $engine)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        $answer = $engine->answer(trim($request->question));

        return response()->json([
            'status' => true,
            'answer' => $answer,
        ]);
    }
}