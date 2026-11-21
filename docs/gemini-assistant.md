# Gemini assistant

Set these values in the local `.env`:

```dotenv
GEMINI_API_KEY=your-key-here
GEMINI_MODEL=gemini-3.1-flash-lite
```

Then run `php artisan config:clear`. Restart long-running application workers
if applicable. The API key stays on the server and is sent in the
`x-goog-api-key` header; do not prefix it with `VITE_`.

Sign in as a customer and open `/assistant`. The existing form supports
travel questions, database flight searches, and comparisons of two or three
selected flight IDs. Saved responses are available only to their owner.
The model receives the submitted question and the public flight results,
not the customer's account, passport, booking or payment records.

Flight comparison at `/compare?flights[]=1&flights[]=2` uses current database
fares, baggage and seat counts; it does not require Gemini. Select actual
available flights from the search results rather than using example IDs.

Gemini answers are advisory plain text, escaped by the existing Blade view.
They cannot change inventory or execute bookings or payments. Missing keys,
quota errors, invalid credentials, blocked responses and connection errors
are handled without displaying provider response bodies or secrets.

The integration uses Google's [GenerateContent REST API](https://aistudio.google.com/api-reference/generate-content).
Choose a supported model in `GEMINI_MODEL` if project access differs.
Tests use fake HTTP responses and do not consume API quota:

```shell
php artisan test --filter="GeminiAssistantTest|Dev3FlowTest|OmarDiscoveryTest"
```
