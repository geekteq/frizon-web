# Frizon.org — UI/UX Design Document

**Version:** 1.0
**Datum:** 2026-03-30
**Projekt:** Frizon of Sweden — Privat resedagbok och reseplanerare
**Användare:** Mattias och Ulrica (Ullisen)
**Fordon:** Frizze — Adria Twin SPT 600 Platinum 2017

---

## Innehåll

1. [Grafisk profil](#1-grafisk-profil)
2. [Komponentbibliotek](#2-komponentbibliotek)
3. [Sidlayouter](#3-sidlayouter)
4. [Interaktionsmönster](#4-interaktionsmönster)
5. [Responsiv strategi](#5-responsiv-strategi)
6. [Kartdesign](#6-kartdesign)
7. [Implementeringsguide](#7-implementeringsguide)

---

## 1. Grafisk profil

### 1.1 Grunden — logotypen som källa

Logotypen innehåller:
- "FRIZON" i fet, mörk stålblå versalstext
- "of Sweden" i lekfull kursivt/script i mörk teal
- Cirkulär vit ram med illustrerat porträtt av Mattias och Ulrica i sin silvergrå Citroen Jumper
- Bakgrund: dämpad stålblå

Dessa element styr hela den grafiska profilen. Designspråket ska vara **skandinaviskt, personligt, rent men varmt** — som att bläddra i en välskött pappersresedagbok men digitalt.

---

### 1.2 Färgpalett

#### Primärfärger (extraherat ur logotypen)

```
--color-steel-blue:      #5D7E9A   /* Bakgrundsfärg logotyp — primär varumärkesfärg */
--color-slate-dark:      #3D4F5F   /* "FRIZON"-textens mörka slate — rubriker, tyngd */
--color-slate-mid:       #4A6070   /* Mellannivå slate — sekundär text, ikoner */
--color-steel-light:     #8FA4B8   /* Ljusare stålblå — bakgrunder, hover-ytor */
--color-steel-muted:     #BDD0DF   /* Mycket ljus stålblå — kort-bakgrunder, separatorer */
--color-off-white:       #F5F7F9   /* Bakgrund app — nästintill vit med stålblå hint */
--color-white:           #FFFFFF   /* Ren vit — kort-ytor, overlay */
```

#### Accentfärger (kompletterar stålblå-paletten)

```
--color-teal-script:     #2C5F6A   /* "of Sweden"-skriptets mörka teal — accenter, CTA */
--color-teal-mid:        #3D7A87   /* Mellanteal — sekundär accent, aktiva tillstånd */
--color-teal-light:      #6BAAB7   /* Ljus teal — hover, soft highlight */
--color-sand:            #E8DFC8   /* Varm sand — kontrast mot stål, notiser */
--color-sand-dark:       #C4B89A   /* Mörkare sand — borders på sand-komponenter */
```

#### Semantiska färger

```
--color-success:         #4A8C6F   /* Grön med skandinavisk hint — klar, gjord */
--color-success-bg:      #EAF5EF   /* Ljus success-bakgrund */
--color-warning:         #C8862A   /* Varm amber — varning, OBS */
--color-warning-bg:      #FDF3E3   /* Ljus warning-bakgrund */
--color-error:           #B54040   /* Dämpad röd — fel, radera */
--color-error-bg:        #FDEAEA   /* Ljus error-bakgrund */
--color-info:            #5D7E9A   /* = steel-blue — informationsnotiser */
--color-info-bg:         #EAF0F5   /* Ljus info-bakgrund */
```

#### Stopptypsfärger (hållplatskategorier)

```
--color-stop-breakfast:  #E8A44A   /* Varm gul-orange — morgon */
--color-stop-lunch:      #6BAE7A   /* Frisk grön — middag */
--color-stop-dinner:     #7A5F9E   /* Lila kvällston — middag */
--color-stop-fika:       #C47B4A   /* Varm brunröd — fika/kaffe */
--color-stop-sight:      #4A8CC4   /* Klar blå — sevärdhet */
--color-stop-shopping:   #C44A7A   /* Rosa-röd — shopping */
--color-stop-stellplatz: #5D9E7A   /* Naturgrön — uppställning */
--color-stop-wildcamp:   #4A7A5D   /* Mörkgrön — vildtältning */
--color-stop-camping:    #7AAE5D   /* Mellangrönt — campingplats */
```

---

### 1.3 Typografi

Typografin ska matcha logotypens karaktär: FRIZON-texten är ett bold geometric sans-serif, "of Sweden" är ett humant script. Vi speglar denna dualitet i UI:t.

#### Valda typsnitt (Google Fonts — gratis, snabba CDN)

```
Rubriker:    "DM Sans" — geometric sans-serif, modern men med personlighet
             Alternativ: "Inter" om DM Sans ej finns

Brödtext:    "DM Sans" Regular/Medium — enhetlig med rubriker men lättläst

Accent/UI:   "DM Sans" Medium — knappar, etiketter, navigation

Script:      "Dancing Script" Bold — används SPARSAMT för dekorativa element
             som matchar "of Sweden"-logostilen (taglines, tom-state text)
```

#### Typsnittsskala

```
/* Rubriknivåer */
--text-h1:    2.0rem   / 32px — Sidrubriker (bara på desktop)
--text-h2:    1.5rem   / 24px — Sektionsrubriker
--text-h3:    1.25rem  / 20px — Kortrubriker, modal-rubriker
--text-h4:    1.125rem / 18px — Undernivårubriker

/* Brödtext */
--text-body-lg:  1.0rem   / 16px — Standard brödtext
--text-body:     0.9375rem/ 15px — Korttext, list-items
--text-body-sm:  0.875rem / 14px — Sekundär information, metadata

/* UI-element */
--text-label:    0.875rem / 14px — Formetiketter, fältnamn
--text-caption:  0.75rem  / 12px — Bildtexter, tidsstämplar, badges
--text-button:   0.9375rem/ 15px — Knapptext (medium weight)
--text-nav:      0.75rem  / 12px — Mobilnavigation etiketter

/* Script/dekorativt */
--text-script-lg: 1.5rem  / 24px — Dekorativa accenter
--text-script-md: 1.125rem/ 18px — Taglines, tom-state
```

#### Fontvikter

```
--weight-regular: 400
--weight-medium:  500
--weight-semibold: 600
--weight-bold:    700
```

#### Radavstånd

```
--leading-tight:  1.25  — Rubriker
--leading-normal: 1.5   — Brödtext
--leading-relaxed: 1.6  — Längre texter, anteckningar
```

---

### 1.4 Avståndsskala

Baserat på 4px-grid. Alla avstånd är multiplar av 4px.

```
--space-1:   4px   /* Inre padding tight, separatorer */
--space-2:   8px   /* Tight komponentpadding */
--space-3:   12px  /* Tight sektionsavstånd */
--space-4:   16px  /* Standard komponentpadding */
--space-5:   20px  /* Mellanstor padding */
--space-6:   24px  /* Sektionsavstånd */
--space-8:   32px  /* Stora sektioner */
--space-10:  40px  /* Mellan-sektioner */
--space-12:  48px  /* Stora sektioner */
--space-16:  64px  /* Sidhuvud-höjder, hero-avstånd */
--space-20:  80px  /* Mobilnavigationens bottenoffset */
```

---

### 1.5 Kantradie och skuggor

```
/* Kantradie */
--radius-sm:    4px   /* Taggar, badges, small inputs */
--radius-md:    8px   /* Knappar, inputfält */
--radius-lg:    12px  /* Kort, modaler */
--radius-xl:    16px  /* Bottom sheets, stora kort */
--radius-2xl:   24px  /* FAB, pill-knappar */
--radius-full:  9999px /* Cirkulära element, avatarer */

/* Skuggor */
--shadow-sm:    0 1px 3px rgba(61, 79, 95, 0.10), 0 1px 2px rgba(61, 79, 95, 0.06);
--shadow-md:    0 4px 6px rgba(61, 79, 95, 0.10), 0 2px 4px rgba(61, 79, 95, 0.06);
--shadow-lg:    0 10px 15px rgba(61, 79, 95, 0.12), 0 4px 6px rgba(61, 79, 95, 0.05);
--shadow-xl:    0 20px 25px rgba(61, 79, 95, 0.15), 0 10px 10px rgba(61, 79, 95, 0.04);
--shadow-card:  0 2px 8px rgba(61, 79, 95, 0.10);
--shadow-float: 0 8px 24px rgba(61, 79, 95, 0.20); /* FAB, bottom sheet */
```

---

### 1.6 Ikonestil

- **Bibliotek:** Lucide Icons (lätt, konsistent, öppet källkod, SVG-baserat)
- **Stil:** Stroke-baserade ikoner, 2px stroke-width
- **Storlekar:**
  - Navigation: 24px
  - Åtgärdsknappar: 20px
  - Inline/text: 16px
  - FAB: 28px
- **Färg:** Ärver `currentColor` för enkel theming

#### Kategoriikoner (stopptyper)

```
breakfast    → coffee (Lucide)         #E8A44A
lunch        → sun (Lucide)            #6BAE7A
dinner       → moon (Lucide)           #7A5F9E
fika         → cup-saucer / coffee     #C47B4A
sight        → landmark (Lucide)       #4A8CC4
shopping     → shopping-bag (Lucide)   #C44A7A
stellplatz   → parking-square          #5D9E7A
wild_camping → tent (Lucide)           #4A7A5D
camping      → home (Lucide)           #7AAE5D
```

---

### 1.7 Rörelsestil (animationsfilosofi)

- Rörelser ska vara **snabba och ändamålsenliga** — aldrig dekorativa för sin egen skull
- Swipe-gester: direkt, 1:1 med fingerrörelse
- Sidövergångar: 200ms ease-out
- Modaler/bottom sheets: 250ms ease-out (in), 200ms ease-in (out)
- Skeleton-laddning: 1.5s shimmer-animation
- Toast-notiser: slide-in 200ms, auto-dismiss 3s, fade-out 200ms

```css
--transition-fast:   150ms ease-out
--transition-normal: 200ms ease-out
--transition-slow:   300ms ease-out
--transition-spring: 250ms cubic-bezier(0.34, 1.56, 0.64, 1)
```

---

## 2. Komponentbibliotek

### 2.1 Navigation

#### Mobilnavigation (primär — bottom bar)

Fastgjord längst ner på skärmen. Alltid synlig på privata sidan.

```
Höjd: 64px + safe-area-inset-bottom (iOS home indicator)
Bakgrund: #FFFFFF
Topplinje: 1px solid #BDD0DF
Skugga: 0 -4px 12px rgba(61, 79, 95, 0.08)

5 flikar:
┌─────────────────────────────────────────────┐
│  [map]      [list]    [+ FAB]   [route]   [☰] │
│  Karta     Platser             Resor    Mer   │
└─────────────────────────────────────────────┘

Aktiv flik:
- Ikonfärg: --color-teal-script (#2C5F6A)
- Etikettfärg: --color-teal-script
- Liten filled indicator-dot ovanför ikonen (4px, teal)

Inaktiv flik:
- Ikonfärg: --color-slate-mid (#4A6070)
- Etikettfärg: --color-slate-mid
- Opacity: 0.65

FAB (mittenknappen — "Lägg till plats"):
- Cirkel: 56px diameter
- Bakgrund: --color-teal-script (#2C5F6A)
- Ikon: map-pin+ (custom) eller plus, 28px, vit
- Skugga: --shadow-float
- Lyfts 12px ovanför navbar (negativ margin-top)
- Touch target: hela 64px höjden
- Etikett: "Spara här" (visas ej, men aria-label)
```

#### Desktopnavigation (sidebar)

```
Bredd: 240px (kollapsbar till 64px)
Position: fixed vänster
Höjd: 100vh
Bakgrund: #3D4F5F (--color-slate-dark)
Text/ikoner: #FFFFFF och rgba(255,255,255,0.65)

Logotyp-area (topp):
- Höjd: 64px
- Logotyp-bild + "Frizon" text
- Separator: 1px rgba(255,255,255,0.1)

Navigationsitems:
- Höjd per item: 48px
- Padding: 0 16px
- Ikon: 20px + 12px gap + text
- Hover: rgba(255,255,255,0.08) bakgrund
- Aktiv: rgba(255,255,255,0.15) + vänster border 3px #6BAAB7

Sektioner:
- "PRIVAT" (label, 11px, uppercase, rgba(255,255,255,0.4))
  - Karta
  - Platser
  - Resor
  - Listor
  - Publiceringskö

- "PUBLIK" (label)
  - Förhandsgranskning

Footer (botten):
- Inloggad användare (avatar-initialer + namn)
- Logga ut-knapp
```

---

### 2.2 Kort (Cards)

#### Platskort (Place Card)

```
┌─────────────────────────────────────┐
│ [Bild 80x80 rounded]  Platsnamn     │
│                        ★ 4.2 · Typ  │
│                        📍 Land       │
│                        Senast: datum │
└─────────────────────────────────────┘

Stil:
- Bakgrund: #FFFFFF
- Border: 1px solid #BDD0DF
- Kantradie: --radius-lg (12px)
- Skugga: --shadow-card
- Padding: 12px
- Bild: 80x80, radius 8px, object-fit cover
- Rubrik: 15px semibold, --color-slate-dark
- Rating: 13px, stjärna i --color-warning
- Typ-badge: kategoriikon + etikett, 12px
- Metadata: 12px, --color-slate-mid
- Touch: aktiv state sänker opacity till 0.85
- Min höjd: 88px
```

#### Listvy platskort (kompakt)

```
┌──────────────────────────────────────────┐
│ [Ikon/typ] Platsnamn            [Pil ›]  │
│            Land · Antal besök            │
│            [Kategori-badge]    ★ 4.2     │
└──────────────────────────────────────────┘

Höjd: 72px
Separator: 1px solid --color-steel-muted
Padding: 12px 16px
Swipe-aktiverat (höger = skapa besök, vänster = alternativ)
```

#### Resekort (Trip Card)

```
┌─────────────────────────────────────────┐
│ [Omslagsbild 100%x120px]               │
│ ████████████ (gradient overlay nertill) │
│ Resenamn                    [Status]    │
├─────────────────────────────────────────┤
│ Start → Slut datum                      │
│ N hållplatser · X km · Y timmar         │
│ [Publik badge?]          [›]            │
└─────────────────────────────────────────┘

Omslagsbild: 100% bredd, 120px höjd, cover
Gradient: linear-gradient(transparent 40%, rgba(61,79,95,0.7))
Rubriken ovanpå gradient: vit text
Status-badge: "Planerad" / "Pågående" / "Avslutad"
Nederdel: padding 12px, bakgrund #FFFFFF
```

#### Besökskort (Visit Card, i platslista)

```
┌───────────────────────────────────────┐
│ [Datum, månadsformat]   [★★★★☆ 4.2]  │
│ Anteckning-preview (2 rader max)      │
│ [Bild] [Bild] [Bild]   +N fler        │
│ [Taggar…]                             │
└───────────────────────────────────────┘

Bakgrund: --color-off-white (#F5F7F9)
Border: 1px solid #BDD0DF
Radius: 8px
Padding: 12px
Thumbnail-bilder: 56x56, radius 6px, 3 visade
```

---

### 2.3 Knappar

#### Primär knapp

```css
/* .btn-primary */
background: #2C5F6A;       /* --color-teal-script */
color: #FFFFFF;
padding: 12px 24px;
border-radius: 8px;        /* --radius-md */
font-size: 15px;
font-weight: 600;
border: none;
min-height: 48px;          /* Tillgänglighet touch target */
cursor: pointer;
transition: background 150ms ease-out;

/* Hover */
background: #3D7A87;       /* --color-teal-mid */

/* Aktiv */
background: #1E4A53;
transform: translateY(1px);

/* Disabled */
background: #BDD0DF;
color: #8FA4B8;
cursor: not-allowed;
```

#### Sekundär knapp

```css
/* .btn-secondary */
background: transparent;
color: #2C5F6A;
border: 2px solid #2C5F6A;
padding: 10px 24px;
border-radius: 8px;
font-size: 15px;
font-weight: 600;
min-height: 48px;

/* Hover */
background: rgba(44, 95, 106, 0.08);
```

#### Ghost-knapp

```css
/* .btn-ghost */
background: transparent;
color: #4A6070;
border: none;
padding: 10px 16px;
border-radius: 8px;
font-size: 15px;
min-height: 44px;

/* Hover */
background: rgba(61, 79, 95, 0.08);
```

#### Destruktiv knapp

```css
/* .btn-danger */
background: #B54040;
color: #FFFFFF;
/* Övriga egenskaper som .btn-primary */
```

#### FAB (Floating Action Button — "Lägg till plats")

```css
/* .fab-add-place */
width: 56px;
height: 56px;
border-radius: 50%;
background: #2C5F6A;
color: #FFFFFF;
border: none;
box-shadow: 0 8px 24px rgba(61, 79, 95, 0.20);
display: flex;
align-items: center;
justify-content: center;

/* Utökat FAB (med label, används på tomt stadie) */
/* .fab-add-place--extended */
width: auto;
border-radius: 28px;
padding: 0 20px;
gap: 8px;
white-space: nowrap;
```

#### Knappstorlekar

```
--btn-sm: padding 8px 16px, font 13px, min-height 36px
--btn-md: padding 12px 24px, font 15px, min-height 48px  (standard)
--btn-lg: padding 14px 28px, font 16px, min-height 52px
```

---

### 2.4 Formulär

#### Textinput

```css
/* .form-input */
width: 100%;
min-height: 48px;
padding: 12px 16px;
border: 1.5px solid #BDD0DF;
border-radius: 8px;
background: #FFFFFF;
font-size: 16px;      /* 16px+ förhindrar iOS-zoom */
color: #3D4F5F;
transition: border-color 150ms ease-out;

/* Focus */
border-color: #3D7A87;
outline: none;
box-shadow: 0 0 0 3px rgba(61, 122, 135, 0.15);

/* Error */
border-color: #B54040;
box-shadow: 0 0 0 3px rgba(181, 64, 64, 0.10);

/* Label */
font-size: 14px;
font-weight: 500;
color: #4A6070;
margin-bottom: 6px;
display: block;
```

#### Textarea (råanteckning)

```css
/* .form-textarea */
/* Samma som input men: */
min-height: 120px;
padding: 12px 16px;
resize: vertical;
line-height: 1.6;
font-family: inherit;

/* Antecknings-textarea (rå intern anteckning) */
/* .form-textarea--note */
background: #FDFCF8;    /* Svag pergament-tint */
border-color: #C4B89A;  /* Sand-ton */
font-size: 16px;
```

#### Select

```css
/* .form-select */
/* Samma som input */
appearance: none;
background-image: url("data:image/svg+xml,...chevron-down...");
background-repeat: no-repeat;
background-position: right 12px center;
padding-right: 40px;
cursor: pointer;
```

#### Betygsinput (Sub-ratings)

Fem sub-ratings visas som en grupp. Varje rating är 1–5.

```
Layoutstruktur för en sub-rating:
┌─────────────────────────────────────────┐
│ Läge          [1] [2] [3] [4] [5]      │
│ Lugn          [1] [2] [3] [4] [5]      │
│ Service       [1] [2] [3] [4] [5]      │
│ Värde         [1] [2] [3] [4] [5]      │
│ Återkomst     [1] [2] [3] [4] [5]      │
│ ─────────────────────────────────────  │
│ Totalt:  ★ 3.8                         │
└─────────────────────────────────────────┘

Betygsknappar:
- Omarkerad: cirkel 36px, border 1.5px #BDD0DF, bakgrund vit
- Markerad:  ifylld cirkel #2C5F6A, text vit
- Hover:     border-color #3D7A87, bakgrund rgba(61,122,135,0.1)
- Animering: scale(1.1) vid markering, 150ms spring

Total visas beräknad: (sum / count) med 1 decimal
Stjärnikoner i --color-warning
```

#### Tagg/autocomplete-input (t.ex. "Passar för")

```
┌─────────────────────────────────────────┐
│ [tagg 1 ×] [tagg 2 ×] [________________│
│            Skriv och tryck Enter...     │
└─────────────────────────────────────────┘
  ↓ Autocomplete-lista (om matches finns)
┌─────────────────────────────────────────┐
│ ▸ Familjer med barn                     │
│ ▸ Hundägare                             │
│ ▸ Cyklister                             │
└─────────────────────────────────────────┘

Taggar:
- Bakgrund: #EAF0F5
- Text: #3D4F5F
- Kantradie: 4px
- Padding: 4px 8px
- ×-knapp: 16px, #8FA4B8 → #B54040 hover

Autocomplete-lista:
- Bakgrund: #FFFFFF
- Border: 1px solid #BDD0DF
- Skugga: --shadow-lg
- Z-index: 100
- Max höjd: 200px, scroll
```

#### Fotouppladdning

```
┌────────────────────────────────────────┐
│                                        │
│        [Kamera-ikon 32px]              │
│   "Lägg till foton"                    │
│   Tryck för att ta foto eller          │
│   välj från galleri                    │
│                                        │
└────────────────────────────────────────┘

Droppzone-stil:
- Border: 2px dashed #BDD0DF
- Bakgrund: #F5F7F9
- Kantradie: 12px
- Min-höjd: 120px
- Vid hover/drag: border-color #3D7A87, bakgrund #EAF0F5

Förhandsgranskning av uppladdade bilder:
┌────┐ ┌────┐ ┌────┐ ┌────┐
│Img1│ │Img2│ │Img3│ │+N  │
└────┘ └────┘ └────┘ └────┘

Miniatyrbilder: 80x80, radius 6px
×-knapp ovanpå varje (24px, röd)
```

---

### 2.5 Checklistor med swipe-gester

#### Listitem (vila/standard)

```
┌──────────────────────────────────────────┐
│ [☐] Texten på listpunkten          [⠿]  │
└──────────────────────────────────────────┘

Höjd: 56px
Padding: 0 16px
Bakgrund: #FFFFFF
Separator: 1px solid #F0F4F7
Ikon ☐: 24px, border 2px #BDD0DF, radius 4px
Drag-handtag ⠿: opacity 0.3, visas bara i sorteringsläge
```

#### Swipe höger (Markera klar)

```
Riktning: → (höger)
Bakgrundsfärg: #4A8C6F (--color-success), synlig bakom item
Swipe-ikon: check-mark 24px, vit
Text: "Klar"
Trigger-tröskel: 80px drag
Fullswipe (>80%): automatisk trigger
Animation: item glider ut höger vid trigger

Klar-state:
- Text: genomstruken, opacity 0.5
- Ikon: fylld grön bock
- Bakgrund: #EAF5EF (--color-success-bg)
```

#### Swipe vänster (Redigera/Radera)

```
Riktning: ← (vänster)
Bakgrundsfärg: #3D4F5F
Två åtgärder synliga:
┌──────────────────┐
│ [Pensel] [Soptunna]│
│ Ändra   Radera    │
└──────────────────┘

Ändra: 72px bredd, #5D7E9A bakgrund
Radera: 72px bredd, #B54040 bakgrund
```

#### Lång-press (Sortering)

```
Aktiveras efter: 500ms lång-press
Visual feedback: item "lyfts" med scale(1.02), shadow-float
Drag-handtag ⠿ tonas in med opacity 1
Auto-scroll vid kant
Släppzon: visuell linje indikerar ny position
```

---

### 2.6 Modaler och bottom sheets

#### Bottom Sheet (mobil primär modal)

```css
/* .bottom-sheet */
position: fixed;
bottom: 0; left: 0; right: 0;
background: #FFFFFF;
border-radius: 20px 20px 0 0;
box-shadow: 0 -8px 40px rgba(61, 79, 95, 0.20);
z-index: 200;
max-height: 90vh;
overflow-y: auto;

/* Drag-handtag */
/* .bottom-sheet__handle */
width: 40px; height: 4px;
border-radius: 2px;
background: #BDD0DF;
margin: 12px auto 0;

/* Backdrop */
/* .bottom-sheet-backdrop */
position: fixed; inset: 0;
background: rgba(61, 79, 95, 0.40);
backdrop-filter: blur(2px);
z-index: 199;
```

#### Desktop Modal

```css
/* .modal */
position: fixed;
inset: 0;
z-index: 200;
display: flex;
align-items: center;
justify-content: center;

/* .modal__dialog */
background: #FFFFFF;
border-radius: 16px;
box-shadow: var(--shadow-xl);
width: 480px;
max-width: calc(100vw - 32px);
max-height: 80vh;
overflow-y: auto;
padding: 24px;
```

---

### 2.7 Toast-notiser

```
Position: top-right (desktop), top-center (mobil)
Bredd: 320px max
Min-höjd: 52px

┌─────────────────────────────────────┐
│ [✓] Platsen har sparats!      [×]   │
└─────────────────────────────────────┘

Varianter:
- Success: grön vänsterram (#4A8C6F), bakgrund #EAF5EF
- Warning: amber vänsterram (#C8862A), bakgrund #FDF3E3
- Error:   röd vänsterram (#B54040), bakgrund #FDEAEA
- Info:    blå vänsterram (#5D7E9A), bakgrund #EAF0F5

Animering:
- Slide in: translateY(-8px) → 0, fade in, 200ms
- Auto-dismiss: 4 sekunder (8s för error)
- Fade out: opacity 0, 200ms
```

---

### 2.8 Betygsdisplay

#### Stjärnbetyg (läs-only, kompakt)

```
★★★★☆ 4.2   — Gul stjärna (#C8862A), tomma halvt transparenta
Storlekar: sm (14px), md (18px), lg (24px)
```

#### Sub-ratings i detaljvy

```
┌─────────────────────────────────┐
│ Betyg                           │
├─────────────────────────────────┤
│ Läge         ●●●●○  4           │
│ Lugn         ●●●●●  5           │
│ Service      ●●●○○  3           │
│ Värde        ●●●●○  4           │
│ Återkomst    ●●●●●  5           │
├─────────────────────────────────┤
│ TOTALT:      ★ 4.2              │
└─────────────────────────────────┘

Fyllda prickar: #2C5F6A (teal)
Tomma prickar: #BDD0DF
Prick-storlek: 10px, gap 4px
```

---

### 2.9 Skeleton-laddning

Alla dynamiska listor och kort ska ha skeleton-state.

```css
/* .skeleton */
background: linear-gradient(
  90deg,
  #F0F4F7 25%,
  #E2EBF0 50%,
  #F0F4F7 75%
);
background-size: 200% 100%;
animation: skeleton-shimmer 1.5s infinite;
border-radius: 4px;

@keyframes skeleton-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

---

## 3. Sidlayouter

*Notera: "Mobile" = max 640px. Alla mått i px om inget annat anges.*

---

### 3.1 Inloggning

```
┌─────────────────────────────────────────┐
│                                         │
│          [Frizon logotyp]               │
│          Frizon of Sweden               │
│                                         │
│   ┌─────────────────────────────────┐   │
│   │ E-postadress                    │   │
│   └─────────────────────────────────┘   │
│                                         │
│   ┌─────────────────────────────────┐   │
│   │ Lösenord                   [👁] │   │
│   └─────────────────────────────────┘   │
│                                         │
│   ┌─────────────────────────────────┐   │
│   │       Logga in                  │   │  ← btn-primary, full-width
│   └─────────────────────────────────┘   │
│                                         │
│   Glömt lösenordet?                     │
│                                         │
└─────────────────────────────────────────┘

Sida:
- Bakgrund: --color-steel-blue (#5D7E9A) gradient ner till #8FA4B8
- Formulärbox: vit, padding 32px, radius 16px, shadow-xl
- Logotyp: 120px bredd, centrerad
- Tagline under logotyp: "of Sweden" i Dancing Script 22px, #FFFFFF

Fel-tillstånd:
- Röd border på fält
- Rött felmeddelande under formuläret
- Skaka formulärbox: 400ms shake-animation
```

---

### 3.2 Dashboard

```
MOBIL:
┌────────────────────────────┐
│ ≡  Frizon          [+GPS]  │  ← Header (56px)
├────────────────────────────┤
│ Hej Mattias! ☀️            │
│ "of Sweden"-tagline        │
├────────────────────────────┤
│ [KARTA — 200px höjd]       │  ← Mini leaflet-karta
│ [Platser nära dig]         │
├────────────────────────────┤
│ SENASTE AKTIVITET          │
│ ┌──────────────────────┐   │
│ │ [Besökskort]         │   │
│ └──────────────────────┘   │
│ ┌──────────────────────┐   │
│ │ [Besökskort]         │   │
│ └──────────────────────┘   │
├────────────────────────────┤
│ PÅGÅENDE RESOR             │
│ ┌──────────────────────┐   │
│ │ [Resekort]           │   │
│ └──────────────────────┘   │
├────────────────────────────┤
│ STATISTIK                  │
│ [N platser] [N besök]      │
│ [N resor]   [N länder]     │
├────────────────────────────┤
│       [Botnavbar]          │
└────────────────────────────┘

DESKTOP:
┌──────────┬─────────────────────────────────────┐
│ SIDEBAR  │ Header                               │
│          ├─────────────────────────────────────┤
│          │  ┌──────────┐  ┌──────────────────┐  │
│          │  │ Mini-karta│  │ Senaste besök    │  │
│          │  │ (300px)  │  │ (lista 3-5)      │  │
│          │  └──────────┘  └──────────────────┘  │
│          │                                      │
│          │  ┌──────────────────────────────────┐ │
│          │  │ Pågående resa (kort)              │ │
│          │  └──────────────────────────────────┘ │
│          │                                      │
│          │  Statistik-rad: 4 kort               │
└──────────┴─────────────────────────────────────┘

Statistik-kort:
- Fyra kort i rad (2x2 på mobil)
- Ikon + stor siffra + etikett
- Bakgrund #FFFFFF, border #BDD0DF, radius 12px
- Ikon: --color-teal-mid
- Siffra: 2rem bold, --color-slate-dark

GPS-snabbknapp i header:
- Ikon: navigation/map-pin, 24px
- Bakgrund: --color-teal-script
- Storlek: 40px cirkel
- Om GPS aktiv: pulsanimation
```

---

### 3.3 Platser — Index

```
MOBIL:
┌────────────────────────────┐
│ Platser        [🗺] [≡]    │  ← "Karta/Lista"-toggle
├────────────────────────────┤
│ [🔍 Sök platser...    ] [▼]│  ← Filter-knapp
├────────────────────────────┤
│ [Filter-chips:]            │
│ [Alla] [Camping] [Sikt]... │
├────────────────────────────┤
│ LISTVYN:                   │
│ ┌──────────────────────┐   │
│ │ [Kompakt platskort]  │   │
│ └──────────────────────┘   │
│ ┌──────────────────────┐   │
│ └──────────────────────┘   │
│         ... fler           │
├────────────────────────────┤
│ [Botnavbar]                │
└────────────────────────────┘

KARTVYN:
┌────────────────────────────┐
│ Platser        [🗺] [≡]    │
├────────────────────────────┤
│ [Sök + Filter]             │
├────────────────────────────┤
│                            │
│    [LEAFLET KARTA          │
│     full höjd minus        │
│     header + navbar]       │
│                            │
│    [Anpassade markörer]    │
│                            │
└────────────────────────────┘
[Slidbar platslista underifrån 35% vid tap på markör]

Toggle-knappar:
- Aktiv: #2C5F6A bakgrund, vit text
- Inaktiv: transparent, #4A6070 text
- border-radius 8px, padding 8px 16px
- Separerade med 1px border

Filterpanel (bottom sheet på mobil):
┌─────────────────────────────────┐
│ — — — (drag-handle)             │
│ Filtrera platser                │
├─────────────────────────────────┤
│ TYP                             │
│ [Alla] [Camping] [Stellplatz].. │
│ [Sight] [Mat] [Shopping]        │
├─────────────────────────────────┤
│ LAND                            │
│ [Dropdown: Välj land]           │
├─────────────────────────────────┤
│ BETYG                           │
│ Minst: [1☆][2☆][3☆][4☆][5☆]  │
├─────────────────────────────────┤
│ [Rensa filter]   [Visa N st]    │
└─────────────────────────────────┘
```

---

### 3.4 Platsdetalj

```
MOBIL:
┌────────────────────────────┐
│ [← Tillbaka]  Platsen      │
├────────────────────────────┤
│ [BILDGALLERI 100%x220px]   │  ← Swipable bilder
│ ○ ○ ● ○  (paginering)      │
├────────────────────────────┤
│ Platsnamn                  │
│ [Typ-badge]  ★ 4.2  📍Land │
├────────────────────────────┤
│ KARTA (100%x140px)         │  ← Mini-karta med markör
├────────────────────────────┤
│ [GPS-koordinater]          │
│ [Adress om finns]          │
├────────────────────────────┤
│ TAGGAR                     │
│ [tagg] [tagg] [tagg]       │
├────────────────────────────┤
│ BESÖK (N st)       [+ Nytt]│
│ ┌──────────────────────┐   │
│ │ [Besökskort]         │   │
│ └──────────────────────┘   │
│ ┌──────────────────────┐   │
│ └──────────────────────┘   │
├────────────────────────────┤
│ BETYGSSAMMANFATTNING       │
│ (Medelvärde alla besök)    │
├────────────────────────────┤
│ [Redigera plats]           │
│ [Publicera plats]          │
└────────────────────────────┘

Bildgalleri:
- Svepbara bilder (touch-swipe)
- Dot-indikatorer
- Thumbnail-rad under (3+ bilder)
- "Öppna fullskärm" vid tap

Fab i platsdetalj:
- "+ Nytt besök"-knapp (fixed bottom right)
- 56px, --color-teal-script
```

---

### 3.5 Lägg till plats (GPS-flöde)

*Prioritet: SNABB. En knapptryckning startar flödet.*

```
STEG 1: GPS-hämtning (overlay/spinner)
┌────────────────────────────────┐
│                                │
│      [GPS-ikon, pulserar]      │
│   Hämtar din position...       │
│   [Avbryt]                     │
│                                │
└────────────────────────────────┘
Bakgrund: halvtransparent blå overlay
Tid: vanligtvis 1-3 sekunder

Om närhetsdetektering hittar en känd plats:
┌────────────────────────────────────┐
│ Det ser ut som att du är vid:      │
│                                    │
│ [Platskort: Hammarby Camping]      │
│ 45 meter bort                      │
│                                    │
│ [Skapa nytt besök]  [Ny plats]     │
│       [Avbryt]                     │
└────────────────────────────────────┘

STEG 2: Snabbformuläret (Bottom Sheet)
┌──────────────────────────────────────┐
│ — — — (drag-handle)                  │
│ Spara plats                          │
├──────────────────────────────────────┤
│ [Karta 100%x120px med GPS-markör]    │
├──────────────────────────────────────┤
│ Namn *                               │
│ ┌────────────────────────────────┐   │
│ │ Platsnamn...                   │   │
│ └────────────────────────────────┘   │
├──────────────────────────────────────┤
│ Typ                                  │
│ [Breakfast][Lunch][Dinner][Fika]     │
│ [Sight][Shopping][Stellplatz]...     │
│  ← Horisontell scroll av chips      │
├──────────────────────────────────────┤
│ Kort anteckning                      │
│ ┌────────────────────────────────┐   │
│ │ Valfri anteckning...           │   │
│ └────────────────────────────────┘   │
├──────────────────────────────────────┤
│ [📷 Lägg till foto (valfritt)]       │
├──────────────────────────────────────┤
│ [      Spara plats      ]            │  ← Primär knapp
│ [  Avancerade alternativ  ]          │  ← Expanderas
└──────────────────────────────────────┘

"Avancerade alternativ" expanderar:
- Justera koordinater (karta-nudge)
- Land (auto-detekterat)
- Tagg-fält
- Ratings direkt
```

---

### 3.6 Skapa besök

```
MOBIL (full-sida form):
┌────────────────────────────────────┐
│ [←]  Nytt besök                    │
├────────────────────────────────────┤
│ [Platskort (liten, övre del)]      │
├────────────────────────────────────┤
│ DATUM & TID                        │
│ Besökt: [Datum-väljare]            │
├────────────────────────────────────┤
│ RAW ANTECKNING                     │
│ ┌──────────────────────────────┐   │
│ │ Skriv vad du vill...         │   │
│ │                              │   │
│ │                              │   │
│ └──────────────────────────────┘   │
│ (120px min, auto-expand)           │
├────────────────────────────────────┤
│ [+ Lägg till strukturerade fält]   │  ← Kollapsbar
│ ▼ Expanderat:                      │
│   Plus-anteckning                  │
│   ┌────────────────────────────┐   │
│   │ Vad var bra?               │   │
│   └────────────────────────────┘   │
│   Minus-anteckning                 │
│   ┌────────────────────────────┐   │
│   └────────────────────────────┘   │
│   Tips                             │
│   ┌────────────────────────────┐   │
│   └────────────────────────────┘   │
│   Prisnivå  [Gratis][€][€€][€€€]  │
│   Skulle återvända  [Ja] [Kanske] [Nej]│
│   Passar för  [tagg-input]         │
│   Notera  [textfält]               │
├────────────────────────────────────┤
│ BETYG                              │
│ [Sub-ratings komponent]            │
├────────────────────────────────────┤
│ FOTON                              │
│ [Foto-upload komponent]            │
├────────────────────────────────────┤
│ PUBLICERING                        │
│ ○ Privat (standard)                │
│ ○ Redo för publicering             │
├────────────────────────────────────┤
│ [     Spara besök      ]           │
└────────────────────────────────────┘

Prisnivå-knappar:
- "Gratis" / "€" / "€€" / "€€€"
- Toggle-stil: vald = teal-script bakgrund, vit text
- Ej vald = transparent, border

Prissymboler: Lucide "banknote" ikon + text

Skulle återvända (radio-style):
- [Ja ✓] [Kanske ~] [Nej ✗]
- Toggle-knappar, Ja=grön, Kanske=gul, Nej=röd (valda)
```

---

### 3.7 AI-textgenerering (Brodera ut text)

```
Knappen "Brodera ut text":
- Visas nedanför textfält i besöksformuläret
- Stil: sekundär knapp med AI-ikon (sparkles)
- Bakgrund: #FDFCF8 (pergament), border: sand
- Text: "Brodera ut text"

Laddningstillstånd:
┌───────────────────────────────────────┐
│ [Sparkles-ikon, roterar]              │
│ Genererar textförslag...              │
└───────────────────────────────────────┘
Ersätter knappen, 2-5 sek

Resultat-vy (bottom sheet eller inline expansion):
┌───────────────────────────────────────┐
│ — — — (drag-handle)                   │
│ AI-förslag      [✎ Redigera]          │
├───────────────────────────────────────┤
│ ┌───────────────────────────────────┐ │
│ │ Genererad text visas här...       │ │
│ │ (redigerbar textarea)             │ │
│ │                                   │ │
│ └───────────────────────────────────┘ │
├───────────────────────────────────────┤
│ [Generera nytt]   [Använd denna text] │
│ [Avbryt]                              │
└───────────────────────────────────────┘

"Använd denna text" kopierar till approved_public_text-fältet
och markerar ready_for_publish = true (kan ångras)

Visuell distinktion AI-genererat:
- Subtle sparkles-ikon i hörnet av textområdet
- Liten etikett "AI-förslag" under texten
- Bakgrund: svag lila tint (rgba(122, 95, 158, 0.05))
```

---

### 3.8 Resor — Index

```
MOBIL:
┌────────────────────────────┐
│ Resor          [+ Ny resa] │
├────────────────────────────┤
│ PÅGÅENDE                   │
│ [Resekort med omslagsbild] │
├────────────────────────────┤
│ PLANERADE                  │
│ [Resekort]                 │
│ [Resekort]                 │
├────────────────────────────┤
│ AVSLUTADE                  │
│ [Resekort] (komprimerade)  │
├────────────────────────────┤
│ [Botnavbar]                │
└────────────────────────────┘

Statussektioner:
- Pågående: teal vänster-accent
- Planerade: sand vänster-accent
- Avslutade: grå, collapsed som standard (expanderbar)
```

---

### 3.9 Resedetalj

```
MOBIL:
┌──────────────────────────────────┐
│ [← Tillbaka]  Resenamn  [⋮ Mer] │
├──────────────────────────────────┤
│ [OMSLAGSBILD 100%x180px]        │
│ [Gradient-overlay + titel]      │
├──────────────────────────────────┤
│ Start: X datum                  │
│ Slut:  Y datum                  │
│ N hållplatser · X km totalt     │
├──────────────────────────────────┤
│ INTRO-TEXT (om finns)           │
├──────────────────────────────────┤
│ RUTT (mini-karta 100%x160px)    │  ← Alla hållplatser på karta
├──────────────────────────────────┤
│ HÅLLPLATSER          [+ Lägg till]│
│                                  │
│  ● [1] HÅLLPLATS-KORT           │  ← Ordnad lista
│  |                               │
│  |  [Segment: 45 km · 32 min]   │  ← Rutt mellan hållplatser
│  |                               │
│  ● [2] HÅLLPLATS-KORT           │
│  |                               │
│  ● [3] HÅLLPLATS-KORT           │
│                                  │
├──────────────────────────────────┤
│ LISTOR (Checklistor, inköp)     │
│ [Lista-kort] [Lista-kort]       │
├──────────────────────────────────┤
│ EXPORT                          │
│ [GPX] [CSV] [JSON] [Google Maps]│
└──────────────────────────────────┘

Hållplats-kort (stop card) i rutt:
┌──────────────────────────────────┐
│ [Typ-ikon]  Platsnamn    [⋮]    │
│ [Kategori-badge]  Planerat: tid │
│ [Anteckning om finns]           │
│ ≡ Dra för att sortera          │
└──────────────────────────────────┘

Segment-indikator (vertikalt mellan stops):
- Vertikal linje: 2px dashed --color-steel-light
- Pill-etikett centrerat: "45 km · 32 min (95km/h: 28 min)"
- Bakgrund: --color-off-white
- Border: 1px solid --color-steel-muted

Sortering av hållplatser:
- Lång-press aktiverar sorteringsläge
- Drag-handtag visas till vänster
- Linjal-indikator visar var item hamnar
- Spara: knapp "Spara ordning" vid förändring
```

---

### 3.10 Skapa resa

```
STEG 1: Grundinformation
┌──────────────────────────────────┐
│ [←]  Ny resa          [1 av 3]  │
├──────────────────────────────────┤
│ Resenamn *                       │
│ ┌────────────────────────────┐   │
│ │ t.ex. "Normandie 2026"     │   │
│ └────────────────────────────┘   │
│                                  │
│ Start- och slutdatum             │
│ [Startdatum] → [Slutdatum]       │
│                                  │
│ Status                           │
│ [Planerad] [Pågående] [Avslutad] │
│                                  │
│ Intro (valfritt)                 │
│ ┌────────────────────────────┐   │
│ │ Kort beskrivning...        │   │
│ └────────────────────────────┘   │
├──────────────────────────────────┤
│ [     Fortsätt →      ]         │
└──────────────────────────────────┘

STEG 2: Lägg till hållplatser
[Se hållplats-flöde nedan]

STEG 3: Sammanfattning
[Se Rutt-sammanfattning]
```

---

### 3.11 Ruttssammanfattning

```
┌──────────────────────────────────┐
│ Ruttsammanfattning               │
├──────────────────────────────────┤
│ [KARTA — 100%x200px]             │
│ (Rutt ritad med polyline)        │
├──────────────────────────────────┤
│ Totalt:                          │
│ 📍 N hållplatser                 │
│ 🛣️  XXX km                       │
│ ⏱️  XX tim YY min (leverantör)   │
│ 🚐 XX tim YY min (95 km/h)       │
├──────────────────────────────────┤
│ SEGMENT                          │
│ ┌──────────────────────────────┐ │
│ │ Plats A → Plats B            │ │
│ │ 145 km · 1 tim 52 min        │ │
│ │ (95km/h: 1 tim 32 min)       │ │
│ └──────────────────────────────┘ │
│ ┌──────────────────────────────┐ │
│ │ Plats B → Plats C            │ │
│ └──────────────────────────────┘ │
├──────────────────────────────────┤
│ [Beräkna om rutt]               │
│ [Exportera till GPX]            │
└──────────────────────────────────┘
```

---

### 3.12 Listhantering (Checklistor & Inköpslistor)

```
LISTINDEX:
┌──────────────────────────────────┐
│ Listor             [+ Ny lista]  │
├──────────────────────────────────┤
│ Filter: [Alla][Checklistor][Inköp]│
├──────────────────────────────────┤
│ GLOBALA LISTOR                   │
│ ┌─────────────────────────────┐  │
│ │ ☑ Packlista (standard)  18 │  │
│ │ 12/18 klara         [Öppna]│  │
│ └─────────────────────────────┘  │
├──────────────────────────────────┤
│ KOPPLADE TILL RESOR              │
│ ┌─────────────────────────────┐  │
│ │ 🛒 Inköp — Normandie      8 │  │
│ │ Resa: Normandie 2026        │  │
│ └─────────────────────────────┘  │
└──────────────────────────────────┘

LISTDETALJ (checklistvy):
┌──────────────────────────────────┐
│ [←]  Packlista     [⋮]          │
├──────────────────────────────────┤
│ [✓ 12 klara · ○ 6 kvar]         │  ← Framstegsmätare
│ ████████████░░░░  67%            │
├──────────────────────────────────┤
│ KATEGORI: Kläder                 │
│ ┌──────────────────────────────┐ │
│ │ [☑] Regnkläder               │  ← Swipeable
│ │ [☐] Varma strumpor (3 par)   │
│ │ [☐] Mössa                    │
│ └──────────────────────────────┘ │
│ KATEGORI: Elektronik             │
│ ┌──────────────────────────────┐ │
│ │ [☑] Laddare                  │
│ └──────────────────────────────┘ │
├──────────────────────────────────┤
│ ┌──────────────────────────────┐ │
│ │ + Lägg till punkt...         │ │
│ └──────────────────────────────┘ │
├──────────────────────────────────┤
│ [Rensa klara]  [Markera alla ☑] │
└──────────────────────────────────┘

Framstegsmätare:
- Bakgrund: #BDD0DF
- Fylld del: --color-success
- Höjd: 8px, radius: full
- Procent-text: 12px, #4A6070

Snabb-lägg-till (sticky bottom):
- Alltid synlig ovanför tangentbord
- Inmatningsfält + Lägg till-knapp
- Autofokus när fält trycks
```

---

### 3.13 Publiceringskö

```
┌──────────────────────────────────┐
│ Publiceringskö                   │
├──────────────────────────────────┤
│ REDO FÖR GRANSKNING (N st)       │
│ ┌──────────────────────────────┐ │
│ │ [Platsbild]  Platsnamn       │ │
│ │ Besökt: datum                │ │
│ │ AI-text: "Lorem ipsum..."    │ │
│ │ [Granska]          [Avvisa]  │ │
│ └──────────────────────────────┘ │
├──────────────────────────────────┤
│ PUBLICERADE (N st)               │
│ ┌──────────────────────────────┐ │
│ │ [Grön check] Platsnamn       │ │
│ │ Publicerat: datum            │ │
│ │ [Avpublicera]  [Redigera]    │ │
│ └──────────────────────────────┘ │
└──────────────────────────────────┘
```

---

### 3.14 AI-utkastgranskning

```
┌──────────────────────────────────────┐
│ [←]  Granska AI-utkast              │
├──────────────────────────────────────┤
│ [Platskort (mini)]                   │
├──────────────────────────────────────┤
│ INTERN ANTECKNING (ej publik)        │
│ ┌────────────────────────────────┐   │
│ │ Råanteckning: "Vi stannade     │   │
│ │ precis bredvid havet..."       │   │
│ └────────────────────────────────┘   │
├──────────────────────────────────────┤
│ AI-FÖRSLAG ✨                        │
│ ┌────────────────────────────────┐   │
│ │ [Redigerbar textarea med       │   │
│ │  AI-genererad text]            │   │
│ │                                │   │
│ │                                │   │
│ └────────────────────────────────┘   │
│ [🔄 Generera nytt]                   │
├──────────────────────────────────────┤
│ BETYGSSAMMANFATTNING                 │
│ [Sub-ratings display]                │
├──────────────────────────────────────┤
│ FOTON ATT PUBLICERA                  │
│ [Foto-galleri med val-checkboxar]    │
├──────────────────────────────────────┤
│ [Avvisa]        [Godkänn och publicera]│
└──────────────────────────────────────┘
```

---

### 3.15 Publik startsida

*Kartans centerpunkt är standardvy. Ingen inloggning krävs.*

```
┌────────────────────────────────────────────┐
│ FRIZON of Sweden              [Logga in]   │  ← Minimal header
│ (logotyp vänster)                          │
├────────────────────────────────────────────┤
│                                            │
│                                            │
│         [LEAFLET FULLBREDD KARTA]          │
│         (viewport - header höjd)           │
│                                            │
│         [Cluster-markörer för             │
│          publicerade platser]              │
│                                            │
│ ┌──────────────────────────────────────┐   │
│ │ 🔍 Sök platser...                    │   │  ← Karta-overlay
│ │ [Typ ▼] [Land ▼] [Betyg ▼] [⭐ Top]│   │
│ └──────────────────────────────────────┘   │
│  (overlay längst upp på kartan)            │
│                                            │
└────────────────────────────────────────────┘
↓ Scrolla ner
┌────────────────────────────────────────────┐
│ Utvalda platser                            │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐   │  ← Grid
│ │[Platskort]│ │[Platskort]│ │[Platskort]│  │
│ └──────────┘ └──────────┘ └──────────┘   │
│ ┌──────────┐ ┌──────────┐ ...             │
├────────────────────────────────────────────┤
│ Topplistor (manuellt kurerade)             │
│ [Bästa Stellplatz] [Bästa Fika] ...       │
├────────────────────────────────────────────┤
│ Footer: Frizon of Sweden · Mattias & Ulrica│
│         "of Sweden" i script-font         │
└────────────────────────────────────────────┘

Publik header (minimal):
- Logotyp vänster
- Nav: "Platser" · "Topplistan"
- "Logga in" knapp höger (ghost-stil)
- Bakgrund: #FFFFFF, shadow-sm vid scroll
- Höjd: 56px (mobil), 64px (desktop)

Publika platskortet (utåt):
- Bild 100% bredd, 160px höjd
- Namn, typ, land
- Betyg
- Godkänd public description (förtruncerad)
- Länk till platsdetalj
```

---

### 3.16 Publik platsdetalj

```
┌────────────────────────────────────────┐
│ [← Tillbaka till platser]              │
├────────────────────────────────────────┤
│ [BILDGALLERI fullbredd, 280px]         │
│ ● ○ ○  paginering                      │
├────────────────────────────────────────┤
│ Platsnamn                H1            │
│ [Typ-badge]   ★ 4.2   📍 Land         │
├────────────────────────────────────────┤
│ [KARTA 100%x180px]                     │
├────────────────────────────────────────┤
│ Beskrivning (public approved text)     │
│ (full text, ej avkortad)               │
├────────────────────────────────────────┤
│ BETYG                                  │
│ [Sub-ratings display, kompakt]         │
├────────────────────────────────────────┤
│ TAGGAR                                 │
│ [tagg] [tagg]                          │
├────────────────────────────────────────┤
│ [Öppna i Google Maps]                  │
└────────────────────────────────────────┘
```

---

### 3.17 Publik topplista

```
┌──────────────────────────────────────┐
│ Topplistan                           │
├──────────────────────────────────────┤
│ [Filter-tabs: Alla · Camping · Fika] │
├──────────────────────────────────────┤
│ #1 ┌──────────────────────────────┐  │
│    │ [Platsbild] Platsnamn  ★4.8 │  │
│    │ Land · Typ                  │  │
│    └──────────────────────────────┘  │
│ #2 ┌──────────────────────────────┐  │
│    │ ...                          │  │
│    └──────────────────────────────┘  │
└──────────────────────────────────────┘

Rankingnummer:
- #1: Stor, guldton #C4940A, bold 24px
- #2: Silver, #8FA4B8, bold 20px
- #3: Brons, #C47B4A, bold 18px
- #4+: #4A6070, 16px
```

---

## 4. Interaktionsmönster

### 4.1 GPS-snabbflöde (steg för steg)

```
1. TRIGGER
   Användaren trycker GPS-FAB-knappen i navigation
   ELLER tecknet "Spara här" på dashboard.

2. GPS-BEGÄRAN
   - Browser navigator.geolocation.getCurrentPosition()
   - Timeout: 10 sekunder
   - enableHighAccuracy: true
   - Spinner-overlay visas omedelbart

3. NÄRHETSDETEKTERING
   - PHP returnerar platser inom konfigurerbar radie (standard: 100m)
   - Om match hittas → visa "Bekräftelse-dialogen" (bottom sheet)
   - Om ingen match → gå direkt till steg 5

4. BEKRÄFTELSEDIALOGET (om känd plats nära)
   "Det ser ut som att du är vid [Platsnamn] (X m bort)"
   Knappar:
   a) "Skapa nytt besök" → besöksformuläret för den platsen
   b) "Spara som ny plats" → steg 5
   c) "Avbryt" → stänger

5. SNABBFORMULÄRET
   Bottom sheet glider upp (250ms ease-out)
   Mini-karta visar GPS-position
   Fält: Namn*, Typ (chip-row), Anteckning, Foto

6. SPARNING
   Primär knapp: "Spara plats"
   Loading-state: spinner på knappen
   Framgångsnotis: toast "Platsen har sparats!" (grön)
   Sheet stängs automatiskt

7. FELHANTERING
   GPS ej tillgänglig: "GPS är inte tillgänglig. Ange position manuellt?"
   GPS timeout: "Det tog för lång tid. Försök igen?"
   Offline: "Du verkar vara offline. Vill du spara lokalt?"
```

---

### 4.2 Swipe-gester för checklistor

```
SWIPE-HÖGER (Markera klar):

Trigger: Horisontell touch-rörelse > 20px i positiv X-riktning
Visual feedback (realtid):
  - Item glider höger proportionellt med finger
  - Grön bakgrund synas bakom item
  - Bock-ikon fadear in vid 40px drag
  - Vid 80px: "Klar"-text visas

Trigger-punkt: 80px (konfigurerbart)
Full-swipe: >75% av bredden → automatisk trigger

Animation vid trigger:
  1. Item fortsätter glida höger (300ms ease-in)
  2. Försvinner utanför viewport
  3. Item-höjd animeras ner till 0 (200ms)
  4. List-items ovanför/nedan fyller gapet

Ångra:
  Toast visas: "Markerad som klar [Ångra]"
  Ångra-period: 5 sekunder

SWIPE-VÄNSTER (Åtgärder):

Trigger: Horisontell touch-rörelse > 20px i negativ X-riktning
Visual feedback (realtid):
  - Item glider vänster
  - Åtgärds-knappar exponeras bakom item
  - "Redigera" (blå, 72px) + "Radera" (röd, 72px)

Maximum drag: 144px (2 × 72px)
Elastisk känsla: rubber-band mot maximum

Tap på "Redigera": öppnar edit-mode (inline eller bottom sheet)
Tap på "Radera": bekräftelse → radera med animering
Tap utanför: item glider tillbaka (200ms ease-out)

LÅNG-PRESS (Sortering):

Trigger: touch håll > 500ms utan rörelse
Haptisk feedback: vibrate(50ms) om stödd
Visual: item lyfts (scale 1.02, shadow-float, 200ms spring)
Drag: item följer finger, auto-scroll vid kanter
Placering: visuell indikatorlinje vid ny position
Släpp: item placeras, visuell sorteringslinje försvinner

Desktop fallback:
- "Redigera" och "Radera" som synliga ikoner höger på varje item
- Drag-handtag ⠿ alltid synligt
- Hover-bakgrund på items
```

---

### 4.3 Fotouppladdningsflöde

```
1. Tryck på fotouppladdnings-zonen

2. Native action sheet öppnas (iOS/Android):
   "Ta foto" / "Välj från galleri" / "Avbryt"

3. Foto väljs/tas

4. Klientside:
   - Visa omedelbart förhandsgranskning (FileReader API)
   - Skicka async AJAX-upload
   - Loading-ring på miniatyren under uppladdning

5. Serverside:
   - Validera: max 10MB, jpeg/png/webp
   - Skapa varianter: thumbnail (150x150), card (400x300), detail (1200x900)
   - Spara sökväg i session/form

6. Klar:
   - Loading-ring ersätts med bild
   - Liten grön bock 2 sekunder

7. Fel:
   - Röd ×-ikon på miniatyren
   - Toast: "Uppladdningen misslyckades. Försök igen."

Gräns: max 8 foton per besök
Ordning: drag-and-drop för att ändra ordning (desktop)
       lång-press på mobil
```

---

### 4.4 Resordering av hållplatser

```
Aktivering:
- Tryck "Ändra ordning"-knappen (visas alltid i rese-detalj-editläge)
- ELLER lång-press på en hållplats

Sorteringsläge:
- Lista byter till sorteringsläge-stil
- Drag-handtag ⠿ synliga vänster om varje item
- Items har lite mer padding och tydligare avgränsning
- Bakgrundsfärg: #F0F4F7 (sorteringsläge-tint)
- Segment-distanserna gråas ut (inte relevanta under sortering)

Drag:
- Tap+hold på handtaget för att lyfta
- Item följer vertikal finger-rörelse
- Övriga items flödar om sig automatiskt
- Visuell placeholder kvar på ursprungsposition (halvtransparent)

Spara:
- Sticky "Spara ordning"-knapp längst upp/ner
- PUT-request med ny ordning som JSON array
- Optimistisk uppdatering (inte vänta på svar)
- "Ordning sparad" toast vid framgång
```

---

### 4.5 Nära-plats-detektering

```
Bakgrundsdetektering:
- Startar automatiskt på dashboard (kräver GPS-tillstånd)
- Kontroll var 5:e minut (om app aktiv)
- Radie: 100m (konfigurerbart i admin/config)

Notifieringstyper:

TYP A — Kort bakgrundsbesök (under 10 min):
Toast: "Nära [Platsnamn]? Skapa besök?"
[Ja] → besöksformulär för platsen
[Avvisa] → avvisar notis 1 timme

TYP B — Längre vistelse (>10 min på position):
Toast (mer framträdande):
"Du verkar vara vid [Platsnamn]. Logga besöket!"
[Logga besök] [Ej nu]

TYP C — Ny GPS-spara nära känd plats:
Visas som konfirmations-dialog istället för att direkt gå till nytt-plats-form
(se GPS-snabbflöde steg 4)
```

---

## 5. Responsiv strategi

### 5.1 Brytpunkter

```css
/* Mobil-first: design för mobil, lägg till regler för större */

/* XS: Telefon liten */
/* Base CSS — ingen media query */
/* Max ~359px */

/* SM: Telefon standard */
@media (min-width: 360px) { }

/* MD: Stor telefon / Liten platta */
@media (min-width: 640px) { }

/* LG: Platta / Liten laptop */
@media (min-width: 1024px) {
  /* Desktop layout aktiveras */
  /* Sidebar visas */
  /* Bottom navbar döljs */
}

/* XL: Laptop / Desktop */
@media (min-width: 1280px) { }

/* 2XL: Stor skärm */
@media (min-width: 1536px) { }
```

### 5.2 Vad förändras mellan mobil och desktop

| Element | Mobil | Desktop |
|---|---|---|
| Navigation | Bottom navbar (64px) | Vänster sidebar (240px) |
| Layoutmönster | En kolumn | Huvud + sidebar eller 2-3 kolumner |
| Platser-index | Kortlista, hel bredd | Grid 2-3 kolumner |
| Rese-detalj | Vertikal stack | Karta höger, lista vänster |
| Modaler | Bottom sheets | Centered modaler |
| Formulär | Hel bredd | Max 600px centrerat |
| Karta | 100% bredd, begränsad höjd | Delad vy, full höjd |
| Swipe-gester | Touch (primär) | Hover-knappar (fallback) |
| FAB | I bottom navbar | Dold (knappar i sidebar) |
| Bildgalleri | Swipable | Click-navigation + lightbox |
| Checklistor | Swipe-gester | Hover-knappar höger |

### 5.3 Touch-mål och mobilavstånd

```
Minimum touch-mål: 44×44px (Apple HIG standard)
Rekommenderat: 48×48px för primära åtgärder

Regel: Interaktiva element ska aldrig vara under 44px i någon riktning.

Mobil-specifik padding:
- Kortseparation: 12px (standard) → 16px (mobil)
- Listitem-höjd: 56px minimum
- Nav-items: 64px höjd i bottom bar
- FAB: 56px diameter

Safe area insets (iOS notch/home indicator):
padding-bottom: env(safe-area-inset-bottom, 16px);
padding-top: env(safe-area-inset-top, 0px);

Skrollbeteende:
- Momentum scrolling: -webkit-overflow-scrolling: touch
- Snap scrolling för bildgallerier: scroll-snap-type: x mandatory
- Overscroll-behavior: overscroll-behavior-y: contain (förhindra page-scroll i modaler)
```

---

## 6. Kartdesign

### 6.1 Leaflet-basemap

```javascript
// Standard tile-lager (gratis, Scandinavian-vänlig)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 19
});

// Alternativt: CartoDB Voyager (renare, lättare stil)
L.tileLayer(
  'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
  {
    attribution: '© OpenStreetMap © CartoDB',
    maxZoom: 19
  }
);
// Rekommendation: CartoDB Voyager för publik sida (renare look)
// OpenStreetMap för privat sida (mer info för navigering)
```

### 6.2 Markörstilar

#### Privat platsmarkör

```javascript
// Anpassad divIcon per kategori
function createPlaceMarker(place) {
  const color = CATEGORY_COLORS[place.type];
  const icon = CATEGORY_ICONS[place.type];

  return L.divIcon({
    className: '',
    html: `
      <div class="map-marker map-marker--${place.type}">
        <div class="map-marker__bubble">
          <svg class="map-marker__icon">${icon}</svg>
        </div>
        <div class="map-marker__pin"></div>
      </div>
    `,
    iconSize: [36, 44],
    iconAnchor: [18, 44],
    popupAnchor: [0, -44]
  });
}
```

```css
.map-marker__bubble {
  width: 36px;
  height: 36px;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  background: var(--marker-color);
  border: 3px solid #FFFFFF;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.map-marker__icon {
  transform: rotate(45deg); /* Kontra-rotation för att hålla ikon upprätt */
  width: 18px;
  height: 18px;
  color: #FFFFFF;
}

.map-marker__pin {
  width: 2px;
  height: 8px;
  background: var(--marker-color);
  margin: 0 auto;
}
```

#### Cluster-markör

```javascript
// Anpassad cluster-ikon
const clusterIcon = L.divIcon({
  className: 'map-cluster',
  html: `<div class="map-cluster__count">${count}</div>`,
  iconSize: [40, 40]
});
```

```css
.map-cluster {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2C5F6A;
  border: 3px solid #FFFFFF;
  box-shadow: 0 2px 12px rgba(44, 95, 106, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
}

.map-cluster__count {
  color: #FFFFFF;
  font-weight: 700;
  font-size: 14px;
}

/* Storlekssteg baserat på antal */
.map-cluster--sm  { width: 36px; height: 36px; font-size: 12px; }
.map-cluster--md  { width: 44px; height: 44px; font-size: 14px; }
.map-cluster--lg  { width: 52px; height: 52px; font-size: 16px; background: #3D4F5F; }
```

#### GPS-nuläges-markör

```css
.map-marker--current-position {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #4A8CC4;        /* Klar blå */
  border: 3px solid #FFFFFF;
  box-shadow: 0 0 0 8px rgba(74, 140, 196, 0.20);
  animation: pulse-gps 2s infinite;
}

@keyframes pulse-gps {
  0%   { box-shadow: 0 0 0 0px rgba(74, 140, 196, 0.20); }
  70%  { box-shadow: 0 0 0 12px rgba(74, 140, 196, 0); }
  100% { box-shadow: 0 0 0 0px rgba(74, 140, 196, 0); }
}
```

### 6.3 Kartan pop-up

```css
/* Leaflet popup override */
.leaflet-popup-content-wrapper {
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(61, 79, 95, 0.20);
  padding: 0;
  overflow: hidden;
}

.leaflet-popup-content {
  margin: 0;
  width: 240px;
}

.map-popup {
  /* Innehåll */
}

.map-popup__image {
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.map-popup__body {
  padding: 12px;
}

.map-popup__name {
  font-weight: 600;
  font-size: 15px;
  color: #3D4F5F;
  margin: 0 0 4px;
}

.map-popup__meta {
  font-size: 12px;
  color: #4A6070;
  display: flex;
  align-items: center;
  gap: 8px;
}

.map-popup__link {
  display: block;
  padding: 8px 12px;
  background: #2C5F6A;
  color: #FFFFFF;
  text-align: center;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}
```

### 6.4 Ruttlinje (Trip route polyline)

```javascript
// Rutt-polyline-stil
const routePolyline = L.polyline(coordinates, {
  color: '#3D7A87',    /* --color-teal-mid */
  weight: 4,
  opacity: 0.8,
  lineJoin: 'round',
  lineCap: 'round',
  dashArray: null      /* Solid för körda segment, '8 4' för planerade */
});

// Planerade/framtida segment
const plannedPolyline = L.polyline(coordinates, {
  color: '#8FA4B8',
  weight: 3,
  opacity: 0.6,
  dashArray: '8 4'
});
```

---

## 7. Implementeringsguide

### 7.1 CSS-variabeldeklaration (root)

Placeras i `public/css/variables.css` och importeras i `main.css`.

```css
:root {
  /* Varumärkesfärger */
  --color-brand-primary:    #5D7E9A;
  --color-brand-dark:       #3D4F5F;
  --color-brand-mid:        #4A6070;
  --color-brand-light:      #8FA4B8;
  --color-brand-muted:      #BDD0DF;
  --color-brand-off-white:  #F5F7F9;

  /* Accentfärger */
  --color-accent:           #2C5F6A;
  --color-accent-mid:       #3D7A87;
  --color-accent-light:     #6BAAB7;

  /* Sand/varm accent */
  --color-warm:             #E8DFC8;
  --color-warm-dark:        #C4B89A;

  /* Semantiska */
  --color-success:          #4A8C6F;
  --color-success-bg:       #EAF5EF;
  --color-warning:          #C8862A;
  --color-warning-bg:       #FDF3E3;
  --color-error:            #B54040;
  --color-error-bg:         #FDEAEA;
  --color-info:             #5D7E9A;
  --color-info-bg:          #EAF0F5;

  /* Neutraler */
  --color-white:            #FFFFFF;
  --color-bg:               #F5F7F9;
  --color-surface:          #FFFFFF;
  --color-border:           #BDD0DF;
  --color-text:             #3D4F5F;
  --color-text-muted:       #4A6070;
  --color-text-subtle:      #8FA4B8;

  /* Stopptyper */
  --color-stop-breakfast:   #E8A44A;
  --color-stop-lunch:       #6BAE7A;
  --color-stop-dinner:      #7A5F9E;
  --color-stop-fika:        #C47B4A;
  --color-stop-sight:       #4A8CC4;
  --color-stop-shopping:    #C44A7A;
  --color-stop-stellplatz:  #5D9E7A;
  --color-stop-wildcamp:    #4A7A5D;
  --color-stop-camping:     #7AAE5D;

  /* Typografi */
  --font-base:    'DM Sans', 'Inter', system-ui, sans-serif;
  --font-script:  'Dancing Script', cursive;

  /* Textstorlekar */
  --text-xs:    0.75rem;
  --text-sm:    0.875rem;
  --text-base:  1rem;
  --text-lg:    1.125rem;
  --text-xl:    1.25rem;
  --text-2xl:   1.5rem;
  --text-3xl:   2rem;

  /* Vikter */
  --weight-regular:  400;
  --weight-medium:   500;
  --weight-semibold: 600;
  --weight-bold:     700;

  /* Radavstånd */
  --leading-tight:   1.25;
  --leading-normal:  1.5;
  --leading-relaxed: 1.6;

  /* Avstånd */
  --space-1:   0.25rem;   /* 4px */
  --space-2:   0.5rem;    /* 8px */
  --space-3:   0.75rem;   /* 12px */
  --space-4:   1rem;      /* 16px */
  --space-5:   1.25rem;   /* 20px */
  --space-6:   1.5rem;    /* 24px */
  --space-8:   2rem;      /* 32px */
  --space-10:  2.5rem;    /* 40px */
  --space-12:  3rem;      /* 48px */
  --space-16:  4rem;      /* 64px */
  --space-20:  5rem;      /* 80px */

  /* Kantradie */
  --radius-sm:    0.25rem;   /* 4px */
  --radius-md:    0.5rem;    /* 8px */
  --radius-lg:    0.75rem;   /* 12px */
  --radius-xl:    1rem;      /* 16px */
  --radius-2xl:   1.5rem;    /* 24px */
  --radius-full:  9999px;

  /* Skuggor */
  --shadow-sm:    0 1px 3px rgba(61, 79, 95, 0.10), 0 1px 2px rgba(61, 79, 95, 0.06);
  --shadow-md:    0 4px 6px rgba(61, 79, 95, 0.10), 0 2px 4px rgba(61, 79, 95, 0.06);
  --shadow-lg:    0 10px 15px rgba(61, 79, 95, 0.12), 0 4px 6px rgba(61, 79, 95, 0.05);
  --shadow-xl:    0 20px 25px rgba(61, 79, 95, 0.15), 0 10px 10px rgba(61, 79, 95, 0.04);
  --shadow-card:  0 2px 8px rgba(61, 79, 95, 0.10);
  --shadow-float: 0 8px 24px rgba(61, 79, 95, 0.20);

  /* Övergångar */
  --transition-fast:   150ms ease-out;
  --transition-normal: 200ms ease-out;
  --transition-slow:   300ms ease-out;
  --transition-spring: 250ms cubic-bezier(0.34, 1.56, 0.64, 1);

  /* Layout */
  --sidebar-width:     240px;
  --header-height-mob: 56px;
  --header-height-desk: 64px;
  --nav-height-mob:    64px;
  --content-max-width: 1200px;
  --form-max-width:    600px;
}
```

### 7.2 Teckensnitt — laddning

Lägg i `<head>`:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
```

### 7.3 Filstruktur för stilar

```
public/
  css/
    variables.css      ← CSS-variabler (importeras först)
    reset.css          ← Minimal CSS reset
    base.css           ← Basstilsregler (body, typografi, länkar)
    layout.css         ← Sidstruktur, sidebar, header, navbar
    components/
      buttons.css
      cards.css
      forms.css
      modals.css
      navigation.css
      swipe-list.css
      ratings.css
      tags.css
      map.css
      toast.css
      skeleton.css
      gallery.css
    pages/
      auth.css
      dashboard.css
      places.css
      trips.css
      lists.css
      public.css
    utilities.css      ← Hjälpklasser (spacing, text, colors)
    main.css           ← Importerar allt i rätt ordning
```

### 7.4 Publik vs Privat visuell distinktion

```
PRIVAT SIDA:
- Bakgrundsfärg: #F5F7F9 (lätt stålblå hint)
- Header: vit med stålblå logotyp
- Navigation: Bottom bar (mobil), mörk sidebar (desktop)
- Accentfärg: --color-accent (#2C5F6A, teal)
- "Inloggad"-indikator i header

PUBLIK SIDA:
- Bakgrundsfärg: #FFFFFF (renare, mer presenterande)
- Header: vit, mer transparent vid toppen av karta
- Navigation: Minimal (platser, topplista, logga in)
- Karta som hero, inte dold verktyg
- Platskort har mer luftig design
- Inga admin-åtgärder synliga

Visuell gräns-indikator (om admin är inloggad och tittar på publik sida):
- Liten banner högst upp: "Du ser den publika sidan" [Gå till adminvyn]
- Bakgrund: --color-warning-bg med amber vänsterkant
```

### 7.5 Tillgänglighet

```
Minimikrav:
- WCAG 2.1 AA för alla interaktiva element
- Kontrastförhållanden:
  - Stor text (18px+): min 3:1
  - Liten text: min 4.5:1
  - Granskning: slate-dark (#3D4F5F) på off-white (#F5F7F9) = 8.1:1 ✓
  - Vit text på teal-script (#2C5F6A) = 5.1:1 ✓

- Alla knappar: aria-label om bara ikon
- Bottom sheet: aria-modal, focus-trap
- Formulär: aria-required, aria-describedby för fel
- Karta: aria-label på karta-container, alternativa textlistor
- Bilder: alt-text alltid
- Fokusindikatorer: aldrig dolda (ersätt med anpassad, ej ta bort)
  outline: 3px solid #3D7A87; outline-offset: 2px;

Touch-mål:
- Minimum 44×44px för ALLA klickbara element
- Listor: minimum 56px radhöjd

Rörelseinställning:
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

*Dokumentslut. Alla mått i CSS-variabler i sektion 7.1. Alla etiketter på svenska.*

*Versionshistorik: v1.0 — Initialt dokument, 2026-03-30*
