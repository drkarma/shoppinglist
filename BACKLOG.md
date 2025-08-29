# Shopylist – Backlog

> **Principer**
> - Ingen inloggning. Länken är “nyckeln”.
> - Sekretess först: inga spårande cookies, inga tredjepartsdelningar.
> - Enkel, snabb och mobilvänlig upplevelse i butik.

## Nu (pågående)
- **Fälttest & feedback**
  - *Som användare vill jag testköra i butik för att se om flowet håller.*
  - **AC**: samla 3–5 konkreta observationer (vad saknas? vad stör?).
  - **Notes**: inga kodändringar förrän feedbacken är inne.

## Nästa kandidater
1) **Recept → inköpslista (import)**
   - *Som användare vill jag kunna klistra in en recept-URL och få ingredienser som rader.*
   - **AC**: klistra in URL → lista visar ingredienser (namn, mängd); multiplikator för portioner; möjlighet att slå ihop dubbletter; inget krav på inlogg.
   - **Tech**: hämta struktur via `schema.org/Recipe` (JSON-LD/microdata); fallback: fri textklistrare + radtolkning; kända domäner först; server-side fetch med timeouts. Respektera robots.
   - **Risk/Complexity**: M–L.

2) **Undo (ångra) rader**
   - *Jag vill kunna ångra att jag råkade ta bort en rad.*
   - **AC**: “Ångra” visas 5–10 s efter delete; återställer raden.
   - **Tech**: soft-delete i UI + POST återställ; ev. server-trash i 5 min.
   - **Complexity**: M.

3) **Kategorier & sortering**
   - *Jag vill kunna sortera listan efter butiksgång (kategorier) för snabbare shopping.*
   - **AC**: valfria kategorier (t.ex. Frukt/Grönt, Mejeri, Torrvaror); sortering: Okryssade → efter kategori → id.
   - **Tech**: kolumn `category` i `list_items`; enkel dropdown vid add/inline-edit.
   - **Complexity**: M.

4) **Mobila UI-förbättringar**
   - *Jag vill att det ska vara superlätt att pricka små element på mobilen.*
   - **AC**: större kryssrutor/ikoner, sticky “Lägg till”-rad på små skärmar, förbättrat fokus/scroll.
   - **Tech**: CSS/Bootstrap-tweaks; inerti-scroll fix.
   - **Complexity**: S–M.

5) **SSE (nära realtid)**
   - *När flera handlar samtidigt vill jag se ändringar snabbare än 2s polling.*
   - **AC**: ändringar syns <1s hos andra klienter.
   - **Tech**: Server-Sent Events (fallback till polling).
   - **Complexity**: M–L.

6) **Generera bilder att använda för mer tittvänlig sida**
   - **Generera med ChatGPT är tanken**
   - **https://suno.com/s/6nPIln4MGBuKcPD7 har gjort några sånger **
   - **https://suno.com/s/PYRir4vZP86LIAI4 här är en till**



## Senare (icebox)
- **Pris per butik / valutor**
  - *Kunna ange butik eller valuta per lista.*  
  **Tech**: extra fält i listheader; format via `Intl`.

- **Snabbinmatning (komma-separerat)**
  - *Klistra in “mjölk, bröd, smör” → 3 rader.*  
  **Tech**: parser i add-flödet.

- **Dubblettskydd**
  - *Varnas om varan redan finns okryssad.*  
  **Tech**: case-insensitive match på `name` inom lista.

- **Offline/PWA light**
  - *Fortsätt bocka av utan nät och synka senare.*  
  **Tech**: Manifest + Service Worker (cache + queue).

- **Tillgänglighet (a11y)**
  - *Allt ska gå med tangentbord och skärmläsare.*  
  **AC**: WCAG-fokus, ARIA, kontrast.

- **Säkerhetshygien**
  - *Skydda POST mot CSRF utan inloggning.*  
  **Tech**: per-session CSRF-token; meta noindex; stäng `diagnostics` i prod.

- **Backup & rotation**
  - *Behåll de N senaste zip:arna automatiskt.*  
  **Tech**: flagga i `backup.sh` + cron.

## Tekniska TODOs (små)
- **Robust baseUrl bakom proxy**
  - Respektera `X-Forwarded-Proto/Host` när `force_https=true`.

- **Query-param för print-inställningar**
  - Spara togglar i URL (?showPrices=1&showQR=1).

- **Små prestandagrejer**
  - Index på `list_items(list_id, checked, id)` (MySQL).
  - Gzip/deflate + Cache-headers på statiska assets.

---

## Etiketter & prioritering
- **prio:** P1 (hög), P2, P3  
- **typ:** feature, polish, tech-debt, a11y, perf, security  
- **storlek:** S/M/L  
- **status:** next, later, idea

> Tips: Vill du hellre använda GitHub Issues, skapa en *Issue Template* med fälten: **User story**, **AC**, **Tech notes**, **Size**, **Labels**.<o

