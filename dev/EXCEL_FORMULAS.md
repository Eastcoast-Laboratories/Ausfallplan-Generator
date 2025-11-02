# 📐 EXCEL FORMELN - DETAILLIERTE ANALYSE

Extrahiert aus `example.xls` (konvertiert zu ODS für Analyse)

## 🔢 FORMELN IM DETAIL

### 1. **Summen pro Tag (Zeile 9, 20, 31, 42, 52)**

**Spalte B (Tag 1 Summe):**
```excel
=SUM(B2:B7)
```

**Spalte E (Tag 2 Summe):**
```excel
=SUM(E2:E7)
```

**Spalte H (Tag 3 Summe):**
```excel
=SUM(H2:H7)
```

**Spalte K (Tag 4 Summe):**
```excel
=SUM(K2:K7)
```

**Bedeutung:** Summiert die Gewichte (Z-Werte) aller Kinder in diesem Tag.

---

### 2. **Statistik-Spalte "D" (Tage-Anzahl) - Spalte S**

**Für jedes Kind in der Nachrückliste:**
```excel
=COUNTIF($A$1:$K$54, Q6) - V6
```

**Aufschlüsselung:**
- `COUNTIF($A$1:$K$54, Q6)`: Zählt, wie oft der Name des Kindes (in Q6) in ALLEN Tagen vorkommt (A1:K54 = gesamter Bereich)
- `- V6`: Subtrahiert einen Wert aus Spalte V (wahrscheinlich Korrekturfaktor)

**Beispiel für Kind in Q6 (Aaron):**
```excel
=COUNTIF($A$1:$K$54, Q6) - V6
// Zählt "Aaron" in allen Tagen und subtrahiert Korrektur
```

---

### 3. **Statistik-Spalte "Prüfsumme" - Spalte T**

**Für jedes Kind:**
```excel
=T6 * R6
```

**Bedeutung:**
- Multipliziert zwei Werte (wahrscheinlich Tage-Anzahl * Gewicht)
- Ergibt die Gesamt-Kapazität, die dieses Kind belegt

---

### 4. **Statistik-Spalte "⬇️" (First on Waitlist Count) - Spalte U**

**Für jedes Kind:**
```excel
=COUNTIF($A$9:$J$9, Q6) + COUNTIF($A$20:$J$20, Q6) + COUNTIF($A$31:$J$31, Q6) + COUNTIF($A$42:$J$42, Q6) + COUNTIF($A$52:$J$52, Q6)
```

**Aufschlüsselung:**
- Zählt, wie oft der Name in den "First on Waitlist" Zeilen vorkommt
- Zeilen 9, 20, 31, 42, 52 sind die Zeilen mit "→ Name"
- Addiert alle Vorkommen zusammen

**Bedeutung:** Wie oft war dieses Kind "Erster auf der Warteliste"?

---

## 📊 SPALTEN-ZUORDNUNG

Basierend auf den Formeln:

| Spalte | Inhalt | Formel-Typ |
|--------|--------|------------|
| A-K | Tage (Name + Z) | Keine Formeln (Daten) |
| Q | Nachrückliste - Name | Keine Formel (Daten) |
| R | Nachrückliste - Z | Keine Formel (Daten) |
| S | Nachrückliste - D (Tage-Anzahl) | `=COUNTIF($A$1:$K$54, Q6) - V6` |
| T | Prüfsumme | `=T6 * R6` |
| U | Nachrückliste - ⬇️ (First on Waitlist) | `=COUNTIF($A$9:$J$9, Q6) + ...` |
| V | Korrekturfaktor | Keine Formel (Daten) |

---

## 🎯 WICHTIGE ERKENNTNISSE

### 1. **Tage-Anzahl (D-Spalte):**
```excel
=COUNTIF($A$1:$K$54, [KindName]) - [Korrektur]
```
- Zählt Vorkommen des Namens in ALLEN Tagen
- Subtrahiert Korrekturfaktor (wahrscheinlich für Header-Zeilen)

### 2. **First on Waitlist (⬇️-Spalte):**
```excel
=COUNTIF($A$9:$J$9, [KindName]) + COUNTIF($A$20:$J$20, [KindName]) + ...
```
- Zählt Vorkommen in den "→ Name" Zeilen
- Eine Zeile pro 4-Tage-Block (Zeilen 9, 20, 31, 42, 52)

### 3. **Summen pro Tag:**
```excel
=SUM(B2:B7)  // Spalte B = Tag 1 Z-Werte
```
- Einfache Summe der Gewichte

---

## ✅ ZUSAMMENFASSUNG FÜR IMPLEMENTIERUNG

### Formeln, die wir brauchen:

1. **Summen (Zeile 9, 20, etc.):**
   ```php
   $sheet->getCell($coord)->setValue("=SUM(B5:B12)");
   ```

2. **Tage-Anzahl (D-Spalte):**
   ```php
   $sheet->getCell($coord)->setValue("=COUNTIF(\$A\$1:\$K\$54,Q6)-V6");
   ```

3. **First on Waitlist (⬇️-Spalte):**
   ```php
   $sheet->getCell($coord)->setValue("=COUNTIF(\$A\$9:\$J\$9,Q6)+COUNTIF(\$A\$20:\$J\$20,Q6)+...");
   ```

4. **Prüfsumme:**
   ```php
   $sheet->getCell($coord)->setValue("=T6*R6");
   ```

---

## 🔍 OFFENE FRAGEN

1. **Was ist Spalte V (Korrekturfaktor)?**
   - Wahrscheinlich: Anzahl der Header-Vorkommen (z.B. 1, wenn Name auch im Header steht)
   - Oder: Anzahl der "Immer am Ende" Vorkommen

2. **Warum Zeilen 9, 20, 31, 42, 52?**
   - Das sind die "First on Waitlist" Zeilen
   - Eine pro 4-Tage-Block
   - Zeile 9 = Block 1 (Tage 1-4)
   - Zeile 20 = Block 2 (Tage 5-8)
   - etc.

3. **Was ist Spalte T (Prüfsumme)?**
   - `=T6*R6` - aber T6 ist die Zelle selbst?
   - Wahrscheinlich Tippfehler in der Analyse
   - Könnte sein: `=S6*R6` (Tage * Gewicht)
