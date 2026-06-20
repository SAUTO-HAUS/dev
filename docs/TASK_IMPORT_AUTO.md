# Task: Sistem Automat de Import Auto pe sauto.md

**Sursele incluse:** Encar, e-CarsTrade, OPENLane Europe

---

## Scop

Pe sauto.md vreau o sectiune noua unde imi creez **filtre de cautare** (marca, model, an, km, pret etc.), iar sistemul:

1. Cauta automat masini pe **Encar**, **e-CarsTrade** si **OPENLane**
2. Masinile gasite apar intr-o **lista de propuneri** (cu poze, date, pret final calculat)
3. **Eu aleg manual** care masini imi plac si apas "Publica"
4. Doar masinile pe care le-am ales se creeaza **automat ca anunt pe sauto.md** si **pe 999.md**, si se publica automat si pe **Facebook** si **Telegram** (folosind sistemul existent pe sauto.md)
5. Pot introduce si un **link direct** de pe oricare din cele 3 surse, iar masina apare in lista de propuneri

---

## Cum vreau sa arate

### 1. Pagina noua in cabinet: "Filtrele mele de import"

- Lista cu toate filtrele mele active
- Buton **"+ Filtru nou"** unde aleg:
  - **Sursa:** Encar / e-CarsTrade / OPENLane (sau mai multe odata)
  - **Marca si modelul**
  - **Anul** (de la – pana la)
  - **Km maxim**
  - **Pret maxim**
  - Alte criterii: combustibil, cutie, tractiune
- Pentru fiecare filtru pot:
  - Sa-l **activez / dezactivez**
  - Sa-l **rulez acum** (fortat)
  - Sa-l **editez** sau **sterg**
  - Sa vad statistici: cate masini a gasit, cate a importat

### 2. Pagina "Masini propuse pentru publicare"

Aici vad toate masinile gasite de sistem care **asteapta decizia mea**:
- Poze, date complete, **pret final calculat** (cu transport + vama + comisionul meu)
- Sursa (Encar / e-CarsTrade / OPENLane)
- Raport masina (daca exista la sursa)
- **Pentru fiecare masina pot apasa:**
  - **"Publica pe sauto.md"** — creeaza doar anunt pe sauto.md
  - **"Publica pe 999.md"** — creeaza doar anunt pe 999.md
  - **"Publica pe ambele"** — creeaza anunt pe sauto.md SI pe 999.md
  - **"Respinge"** — masina dispare din lista, nu se publica niciodata
- Dupa publicare, masina se muta in sectiunea **"Anunturi publicate"** unde vad unde a fost publicata si pot **sterge** anunturile cand vreau

### 3. Pagina "Introdu link direct"

- Camp pentru link (URL)
- Sistemul detecteaza automat sursa dupa URL (Encar / e-CarsTrade / OPENLane)
- Submit → sistemul ia datele masinii si o adauga in lista "Masini propuse"
- De acolo flow identic cu pagina #2 (eu aleg unde sa publice)

### 4. Setari

Vreau sa pot configura:
- **Adaosul meu comercial** (procent fix, ex: +15%) — sa se adauge automat la pretul de la sursa
- **Costuri de import** per tara sursa (Coreea, Belgia/NL, etc.):
  - Livrare in Moldova
  - Inspectia masinii
  - Evacuator in tara sursa
  - Brokeraj
  - Evacuator in Chisinau
  - Verificare Interpol
  - Procesare documente
  - Taxa de reciclare
  - Schimb de ulei
  - Curatare
  - Formalitati vamale
- **Vama si TVA** (calculat dinamic conform legislatiei MD)
- **Cont 999.md** pe care se publica
- **Notificari:** unde sa-mi vina alertele (Telegram, email)
- **Frecventa cautare:** la fiecare 30 min / 1h / 2h / 6h

---

## Comportament dorit

### Cand sistemul gaseste o masina noua pe sursa:

1. Verifica sa nu fie duplicat (nu o adauga de 2 ori)
2. Descarca toate pozele si le salveaza pe sauto.md
3. Traduce titlul, descrierea, dotarile si raportul tehnic **automat in romana** (din coreeana / engleza / olandeza, etc.) — folosind solutie gratuita (LibreTranslate self-hosted)
4. Converteste pretul in EUR (din KRW pentru Encar, etc.) folosind curs zilnic
5. Calculeaza pretul final cu defalcare detaliata (ca pe caromoto):

```
Pret masina la sursa            3.887 €
Livrare in Moldova              1.850 €
Inspectie                         100 €
Evacuator tara sursa              139 €
Adaos dealer                      300 €
Brokeraj                           50 €
Evacuator Chisinau                 55 €
Verificare Interpol                70 €
Procesare documente               320 €
Taxa reciclare                     51 €
Schimb ulei                       150 €
Curatare                           60 €
Vama                                0 €
TOTAL                           7.032 €
```

Sumele difera in functie de tara de unde vine masina.

6. Preia automat **raportul tehnic** din sursa (daca exista — Encar si OPENLane au raport complet cu poze inspectie, kilometraj verificat, accidente, schimbari proprietar)
7. **O pune in lista "Masini propuse"** si ma anunta prin Telegram/email

### Dupa ce eu apas "Publica":

8. Se creeaza automat anunt pe destinatiile pe care le-am ales (sauto.md, 999.md sau ambele)
9. Toate datele si pozele se trec automat — eu nu rescriu nimic
10. Se trimite si pe Facebook si Telegram (folosind sistemul existent de auto-publicare de pe sauto.md)
11. Pe pagina anuntului apare:
    - Defalcarea pretului (widget expandable, ca pe caromoto)
    - Raportul masinii (tab/sectiune separata cu poze inspectie + date verificate)

### Sincronizare automata (verificare in fiecare noapte):

12. Sistemul intra singur pe sursa si verifica daca masinile publicate inca sunt de vanzare
13. Daca o masina a fost vanduta sau stearsa la sursa:
    - Se marcheaza ca "indisponibil" pe sauto.md
    - Se trimite cerere de ascundere pe 999.md
    - Primesc notificare

**Caut automat, decid eu ce se publica, publicarea se face automat dupa click, si sistemul mentine catalogul curat singur.**

---

## Conturile necesare (le am deja)

| Sursa | Cont necesar? | Status |
|---|---|---|
| **Encar** | NU | — (acces public) |
| **e-CarsTrade** | DA — cont dealer | Am cont activ |
| **OPENLane Europe** | DA — cont dealer | Am cont activ |
| **999.md** | DA — API key | Configurat |

---

## Ce e deja gata pe sauto.md (NU se face de la zero, trebuie legat cu parsingul nou)

Sistemul nou de import trebuie sa **se conecteze cu functionalitatile deja existente**, NU sa le refaca de la zero:

- **Calculatorul de devamare pentru Europa** — logica de calcul a accizei MD exista deja in admin. Pentru Coreea trebuie adaugat un modul similar (cu alte taxe: transport maritim, comision dealer Korea, evacuator port Incheon), iar calculatorul existent trebuie refolosit pentru masinile din Europa.

- **Publicare automata pe 999.md** — exista deja integrare API cu 999.md (Api999Service). Masinile importate trebuie sa foloseasca acest sistem existent, nu sa fie creat unul nou.

- **Auto-publicare pe Facebook** — exista deja cron care publica automat pe Facebook (`scheduled_facebook_posts.php`). Masinile importate trebuie sa intre in coada acestui cron.

- **Auto-publicare pe Telegram** — exista deja cron care publica automat pe Telegram (`scheduled_telegram_posts.php`). Masinile importate trebuie sa intre in coada acestui cron.

- **Scheduler programare anunturi 999.md** — exista deja cron care publica programat pe 999.md (`publish_scheduled_adverts.php`). Masinile importate trebuie sa foloseasca acest scheduler.

- **Orchestrator publicare** — exista deja `AutoPublicationService` si `PublicationService` care orchestreaza tot fluxul de publicare. Modulul nou de import trebuie sa apeleze aceste servicii, nu sa duplice logica.

- **Tabele de configurare taxe** — exista deja in DB (`_calculator_excise_rates`, `_calculator_settings`). Se refolosesc.

**Concluzie pentru developer:** mare parte din infrastructura de publicare automata, calcul devamare si integrare cu 999.md/Facebook/Telegram este deja construita si functioneaza pentru anunturile manuale. Sarcina este sa **conecteze sistemul nou de import (parsing din 3 surse) cu aceasta infrastructura existenta**, nu sa o reconstruiasca.

---

## Ce trebuie sa pun la dispozitia developerului

1. **OPENLane:** acces prin scraping cu cont logat — contractul existent cu OPENLane autorizeaza preluarea datelor. Voi furniza user + parola cont dealer (salvate criptat, nu vizibile altora). Daca apar blocaje tehnice repetate (Cloudflare), voi cere whitelist IP pe baza contractului.
2. **e-CarsTrade:** voi furniza user + parola cont dealer (sa fie salvate criptat, nu vizibile altora)
3. **Valori concrete pentru calculator:**
   - Procent adaos comercial dorit
   - Cost transport pentru fiecare tara (Coreea, Belgia/NL, etc.)
   - Sumele exacte pentru fiecare categorie (inspectie, brokeraj, evacuator, etc.)
   - Procent vama + TVA (sau formula daca exista)
4. **Decizii:**
   - Anunturile apar **direct publice** pe sauto.md sau **trebuie sa le aprob eu manual** mai intai?
   - Pe ce cont user sa apara anunturile (al meu admin sau special "Import")?

---

## Surse explicit excluse din scope

- **Licitare automata** — sistemul doar importa date si publica anunturi, nu liciteaza in numele meu

---

## Termene

| Element | Detalii |
|---|---|
| **Durata dezvoltare** | ~3 luni (11-12 saptamani) |
| **Cost lunar infrastructura** | **0€** |
| **Cost lunar servicii externe** | **0€** (am deja conturile dealer, LibreTranslate gratis self-hosted) |
| **Mentenanta dupa livrare** | Negociabil (abonament lunar sau plata per interventie) |

### Etape de livrare

| Etapa | Continut | Termen |
|---|---|---|
| **Etapa 1** | Tabele DB + UI filtre + Adapter Encar + auto-publicare functionala (sauto.md + 999.md + FB + Telegram) | Saptamana 4 |
| **Etapa 2** | Adapter e-CarsTrade + pipeline complet (poze, traduceri, conversie valuta) | Saptamana 8 |
| **Etapa 3** | Adapter OPENLane + calculator Coreea + raport masina + widget defalcare pret + sincronizare nocturna + testare finala | Saptamana 11-12 |

---

## Cerinte importante

1. **Anunturile pe 999.md trebuie publicate automat** prin API
2. **Auto-publicare pe Facebook si Telegram** — folosind sistemul existent pe sauto.md (cod-ul exista, trebuie doar legat de masinile importate)
3. **Pozele trebuie descarcate local** pe serverul sauto.md (nu link-uri externe care pot disparea)
4. **Traducerile trebuie sa fie gratis** (fara abonament la servicii platite) — LibreTranslate self-hosted
5. **Sistemul trebuie sa tina log** cand o sursa se sparge sau da erori, ca sa stiu sa intervin
6. **Daca o masina dispare de la sursa, anuntul trebuie marcat ca "indisponibil"** pe sauto.md si 999.md (sincronizare nocturna)
7. **Sistemul nu trebuie sa ma blocheze de la sursa** — sa foloseasca pauze intre cautari ca sa nu fiu detectat ca bot
8. **Parolele si credentialele dealer** trebuie stocate criptat in baza de date, nu in text simplu
9. **Codul nou trebuie sa se integreze** cu structura existenta pe sauto.md (sa nu strice nimic din ce functioneaza)
10. **Raportul masinii** (poze inspectie, kilometraj verificat, accidente) preluat automat din sursa cand exista

---

## Observatii pe surse

**Encar (Coreea):**
- Acces public gratuit, fara cont
- Date in coreeana — necesita traducere automata
- Preturi in wons (KRW) — conversie automata in EUR
- Cea mai usoara sursa tehnic

**e-CarsTrade (Belgia):**
- Necesita logare automata cu contul meu dealer
- Risc mic de detectare ca bot — sistemul trebuie sa faca pauze naturale intre cautari
- Platforma e gratuita (fara abonament lunar), platesc doar comision la achizitie

**OPENLane Europe:**
- Acces prin scraping autentificat cu user/parola contul meu dealer
- Avem **contract semnat cu OPENLane care permite preluarea datelor**, deci partea legala e acoperita
- Constrangerea ramasa e tehnica (Cloudflare bot detection) — sistemul foloseste browser real cu pauze naturale 5-15 sec intre cereri
- Daca apar blocaje repetate, cer whitelist IP de la OPENLane pe baza contractului
- Inregistrare, bidding si suport sunt gratuite (confirmat oficial pe site-ul lor)

---

## Functionalitati care raman aceleasi pentru anunturile importate

Anunturile create automat pe sauto.md trebuie sa functioneze exact ca anunturile manuale:
- Vizibile in catalogul public
- Posibilitate de editare manuala daca e nevoie
- Statistici (views, contacte)
- Promovare (top, pin, etc.)
- Stergere

Singura diferenta: au un marker intern ca sunt "Importate automat" si o legatura la sursa originala (pentru sincronizare nocturna).
