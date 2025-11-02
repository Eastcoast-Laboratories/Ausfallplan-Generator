# 📊 ULTRA-DETAILLIERTE EXCEL-STRUKTUR ANALYSE

Basierend auf `example.xls` und `schedule_example.csv`

## 🔲 BORDER-STRUKTUR

### **WICHTIG: Borders nur am RAND um die GANZEN Tage-Blöcke!**

**NICHT** um jede einzelne Zelle, sondern:
- Ein großer Border-Rahmen um ALLE 4 Tage zusammen
- Ein separater Border-Rahmen um die Nachrückliste

```
┌─────────────────────────────────────────────────────────┐
│ Tag 1  Z    Tag 2  Z    Tag 3  Z    Tag 4  Z           │
│ Aaron  1    Jannis 1    Hans   1    Levin  1           │
│ Bo     1    Lene   1    Zaphod 2    Timo   1           │
│ ...                                                     │
│ 9           9           9           9                   │
└─────────────────────────────────────────────────────────┘

┌──────────────────┐
│ Nachrückliste    │
│ Name  Z  D  ⬇️   │
│ Aaron 1  0  0    │
│ Bo    1  0  0    │
│ ...              │
└──────────────────┘
```

## 📏 SPALTEN-STRUKTUR

### Block 1 (Tage 1-4 + Nachrückliste):

| Spalte | Index | Inhalt | Breite | Border |
|--------|-------|--------|--------|--------|
| A | 1 | Tag 1 Name | Breit (~15) | Linker Rand des Blocks |
| B | 2 | Tag 1 Z | Schmal (~5) | - |
| C | 3 | **LEER** | Sehr schmal (~2) | - |
| D | 4 | Tag 2 Name | Breit (~15) | - |
| E | 5 | Tag 2 Z | Schmal (~5) | - |
| F | 6 | **LEER** | Sehr schmal (~2) | - |
| G | 7 | Tag 3 Name | Breit (~15) | - |
| H | 8 | Tag 3 Z | Schmal (~5) | - |
| I | 9 | **LEER** | Sehr schmal (~2) | Rechter Rand des Blocks |
| J | 10 | Tag 4 Name | Breit (~15) | - |
| K | 11 | Tag 4 Z | Schmal (~5) | - |
| L | 12 | **LEER** | Sehr schmal (~2) | - |
| M | 13 | Nachrückliste Name | Breit (~15) | Linker Rand Nachrückliste |
| N | 14 | Z | Sehr schmal (~3) | - |
| O | 15 | D | Sehr schmal (~3) | - |
| P | 16 | ⬇️ | Sehr schmal (~3) | Rechter Rand Nachrückliste |

### Block 2 (Tage 5-8):

| Spalte | Index | Inhalt | Breite |
|--------|-------|--------|--------|
| A | 1 | Tag 5 Name | Breit |
| B | 2 | Tag 5 Z | Schmal |
| C | 3 | **LEER** | Sehr schmal |
| D | 4 | Tag 6 Name | Breit |
| E | 5 | Tag 6 Z | Schmal |
| F | 6 | **LEER** | Sehr schmal |
| G | 7 | Tag 7 Name | Breit |
| H | 8 | Tag 7 Z | Schmal |
| I | 9 | **LEER** | Sehr schmal |
| J | 10 | Tag 8 Name | Breit |
| K | 11 | Tag 8 Z | Schmal |

## 📐 ZEILEN-STRUKTUR

### **WICHTIG: Alle Tage haben die GLEICHE Höhe!**

Jeder 4-Tage-Block hat eine feste Anzahl von Zeilen (z.B. 10 Zeilen), unabhängig davon, wie viele Kinder tatsächlich an einem Tag sind.

### Zeilen-Aufbau pro Block:

| Zeile | Inhalt | Höhe |
|-------|--------|------|
| 1 | Header (Tag-Namen) | Normal |
| 2-8 | Kinder | Normal (alle gleich!) |
| 9 | First on Waitlist ("→ Name") | Normal |
| 10 | Summe (9) | Normal |
| 11 | Leerzeile | Normal |

**Alle Zeilen haben die gleiche Höhe!** Keine Auto-Sizing!

## 📐 FORMELN

### Summen-Formeln (Zeile 13, 14, etc.):

Die Summen sind **FORMELN**, nicht statische Werte!

**Beispiel Zeile 13, Spalte A (Summe Tag 1):**
```
=SUM(B5:B12)
```

**Zeile 13, Spalte D (Summe Tag 2):**
```
=SUM(E5:E12)
```

**Pattern:**
- Jede Summe addiert die Z-Werte (Gewichte) der Kinder in diesem Tag
- Bereich: Von erster Kind-Zeile bis letzte Kind-Zeile (vor "→ Name")

### Prüfsummen (rechts):

Falls vorhanden, könnten Prüfsummen-Formeln sein wie:
```
=SUM(B5,E5,H5,K5)  // Summe der Gewichte für ein Kind über alle 4 Tage
```

## 🎨 FORMATIERUNG

### Spalten-Breiten:

1. **Name-Spalten (A, D, G, J, M):** ~15 Zeichen
2. **Z-Spalten (B, E, H, K, N):** ~5 Zeichen
3. **Statistik-Spalten (O, P):** ~3 Zeichen (SEHR SCHMAL!)
4. **Spacer-Spalten (C, F, I, L):** ~2 Zeichen

### Zeilen-Höhen:

**Alle Zeilen gleich hoch!** (~15 Punkte)

### Borders:

1. **Äußerer Border um den gesamten 4-Tage-Block:**
   - Top: Zeile 4 (Header)
   - Bottom: Zeile 14 (letzte Summe)
   - Left: Spalte A
   - Right: Spalte K (oder I nach Tag 4)

2. **Äußerer Border um Nachrückliste:**
   - Top: Zeile 4
   - Bottom: Letzte Zeile mit Kindern
   - Left: Spalte M
   - Right: Spalte P

3. **KEINE inneren Borders zwischen Zellen!**

### Hintergrund:

- Keine besonderen Farben
- Alles weiß

## ✅ ZUSAMMENFASSUNG

### Was ANDERS ist als gedacht:

1. ✅ **Borders NUR am RAND** um den ganzen Block, nicht um jede Zelle
2. ✅ **Alle Tage gleiche Höhe** - feste Zeilen-Anzahl pro Block
3. ✅ **Statistik-Spalten SEHR SCHMAL** (nur 3 Zeichen breit)
4. ✅ **Formeln für Summen** - `=SUM(B5:B12)` statt statische Zahlen
5. ✅ **Spacer-Spalten** zwischen jedem Tag (leer, sehr schmal)

### Was GLEICH ist:

1. ✅ Nachrückliste nur im ersten Block
2. ✅ 4 Tage pro Block
3. ✅ Name + Z Spalten pro Tag
4. ✅ "→ Name" für First on Waitlist
