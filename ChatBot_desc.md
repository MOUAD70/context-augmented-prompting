# What is Context Augmented Prompting (CAP)?

CAP is a way to make AI give better, more relevant answers — without retraining it. You simply include useful background information *inside the prompt itself*, so the AI has what it needs to respond accurately.

Think of it like briefing someone before a meeting: you hand them the relevant documents and rules upfront, so they can answer confidently on the spot.

---

## What Gets Added to the Prompt?

Instead of changing the AI model itself, you inject three things alongside the user's question:

- **Context** — relevant documents, facts, or data the AI should use.
- **Rules** — boundaries the AI must stay within (e.g. "only answer based on these docs").
- **Instructions** — how the AI should behave or what role it should play.

**Real examples:** company policies, user permission levels, product documentation, operational guidelines.

---

## How It Works

1. **User asks a question.**
2. **The system retrieves relevant context** from a database or file.
3. **A full prompt is assembled** — combining instructions, context, and the user's question.
4. **The prompt is sent to the AI.**
5. **The AI responds** based on what it was given, not just general knowledge.

---

## Pros and Cons

**Why it's useful:**
- Simple to set up — no model training or special infrastructure needed.
- Great for enforcing specific rules and structured logic.
- Works well for smaller, well-defined sets of information.

**Where it falls short:**
- Long prompts use more tokens, which can slow things down and cost more.
- Every model has a maximum prompt size — you can't cram in unlimited information.
- Doesn't scale well to very large document collections (that's where RAG comes in).