# ADR 0011 — Keep source extraction outside the Agent API

The Agent API accepts canonical structured data and does not fetch, crawl, OCR, or use an AI model to interpret webpages, documents, images, or free text. The calling AI agent owns extraction and may attach source URLs or notes as provenance, while ordinary Recipe source metadata remains part of the Recipe itself. This keeps model choice, prompt injection, external-network access, SSRF defenses, and extraction failure outside the application, at the cost of requiring capable clients to translate source material before submission.
