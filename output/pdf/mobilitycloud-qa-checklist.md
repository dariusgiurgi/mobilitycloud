# MobilityCloud QA Checklist

Checklist complet de testare functionala, vizuala, ergonomica si de permisiuni.

Status recomandat pentru fiecare test: PASS / FAIL / BLOCKED / RETEST.

## 0. Reguli de rulare QA

Aceste teste se ruleaza in productie sau staging, pe conturi dedicate. Nu se folosesc date reale de beneficiari pana cand fluxurile de stergere, backup si permisiuni sunt validate.

### QA-0001 - Pregatire conturi de test (P0)

- Preconditii: Ai acces la platform owner/admin si la minimum 5 emailuri de test.
- Pasi:
  - Creeaza sau confirma conturile: workspace owner, workspace admin, member, viewer, project-only collaborator, suspended user.
  - Noteaza emailurile, rolurile, workspace-ul si proiectul asignat.
- Rezultat asteptat: Fiecare rol se poate autentifica si are acces doar la zona asteptata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### QA-0002 - Date minimale de test (P0)

- Preconditii: Workspace activ Writer Pro sau echivalent.
- Pasi:
  - Creeaza doua proiecte active, un proiect fara template, un proiect cu template KA152-YOU, minimum 3 participanti, 2 organizatii, 3 cheltuieli, 2 documente generate si 2 fisiere uploadate.
- Rezultat asteptat: Exista date suficiente pentru dashboard, exporturi, arhive si permisiuni.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### QA-0003 - Browsere si viewporturi (P0)

- Preconditii: Ai Chrome/Brave/Safari si un telefon sau responsive simulator.
- Pasi:
  - Ruleaza cel putin fluxurile P0 in desktop 1440px, laptop 1280px, mobile 390px.
  - Verifica daca sidebar-ul ramane utilizabil si nu necesita scroll vertical inutil.
- Rezultat asteptat: UI-ul este lizibil, fara texte taiate, overlay-uri iesite din ecran sau butoane inaccesibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### QA-0004 - Regula de raportare bug (P0)

- Preconditii: Ai acces la documentul QA.
- Pasi:
  - Pentru fiecare bug noteaza ID test, rol, URL, browser, pasul exact, rezultatul obtinut, screenshot si severitate.
  - Marcheaza status: PASS, FAIL, BLOCKED, RETEST.
- Rezultat asteptat: Bugurile sunt reproductibile si usor de prioritizat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### QA-0005 - Verificare dupa deploy (P1)

- Preconditii: Ultimul commit este deployat pe server.
- Pasi:
  - Acceseaza /app/login, /app, un workspace si un proiect.
  - Verifica in footer/log intern daca nu apar 500/419/403 neasteptate.
- Rezultat asteptat: Aplicatia raspunde stabil dupa cache clear, migrate si queue restart.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 1. Autentificare, cont si sesiune

Acopera intrarea in platforma, iesirea, resetarea parolei, invitatiile si comportamentul conturilor blocate.

### AUTH-0001 - Login valid (P0)

- Preconditii: Cont activ existent.
- Pasi:
  - Deschide /app/login.
  - Introdu email si parola valide.
  - Apasa Sign in.
- Rezultat asteptat: Utilizatorul ajunge in workspace-ul curent sau selectorul de workspace fara eroare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0002 - Login invalid (P0)

- Preconditii: Cont activ existent.
- Pasi:
  - Introdu email valid si parola gresita.
  - Apasa Sign in.
- Rezultat asteptat: Apare mesaj de eroare clar, fara page expired si fara redirect confuz.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0003 - Logout din orice zona (P0)

- Preconditii: Utilizator autentificat.
- Pasi:
  - Apasa meniul din dreapta sus.
  - Apasa Sign out din dashboard, proiect, account suspended daca este cazul.
- Rezultat asteptat: Logout-ul duce la pagina unica de login si nu produce 419.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0004 - Forgot password vizibil (P0)

- Preconditii: Pagina login deschisa.
- Pasi:
  - Verifica existenta linkului Forgot password.
  - Apasa linkul.
- Rezultat asteptat: Se deschide formularul de resetare parola.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0005 - Reset password email (P0)

- Preconditii: SMTP activ, email de test accesibil.
- Pasi:
  - Cere resetarea parolei.
  - Deschide emailul primit.
  - Apasa linkul semnat.
- Rezultat asteptat: Linkul se deschide fara 403 si permite setarea parolei noi.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0006 - Reset password token expirat/invalid (P0)

- Preconditii: Ai un link vechi sau modificat manual.
- Pasi:
  - Deschide linkul invalid.
  - Incearca sa setezi parola.
- Rezultat asteptat: Platforma afiseaza eroare controlata, nu pagina Laravel 500.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0007 - Cont suspendat (P0)

- Preconditii: Cont user suspendat.
- Pasi:
  - Incearca login.
  - Verifica pagina de suspendare.
  - Apasa contact/sign out.
- Rezultat asteptat: Accesul la module este blocat; pagina explica motivul si ofera contact/logout functional.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0008 - Invitatie workspace pentru user nou (P0)

- Preconditii: Owner/admin trimite invitatie catre email nou.
- Pasi:
  - Deschide linkul din email.
  - Creeaza cont cu acelasi email.
  - Accepta invitatia.
- Rezultat asteptat: Userul intra in workspace cu rolul setat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0009 - Invitatie workspace cu email diferit (P0)

- Preconditii: Ai link de invitatie pentru alt email.
- Pasi:
  - Autentifica-te cu alt email.
  - Deschide linkul invitatiei.
- Rezultat asteptat: Platforma refuza accesul cu mesaj clar, fara atasare gresita.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0010 - Invitatie project-only (P0)

- Preconditii: Project access trimite invitatie catre email nou.
- Pasi:
  - Accepta invitatia cu emailul corect.
  - Intra in workspace.
  - Deschide Projects.
- Rezultat asteptat: Userul vede doar proiectul invitat si nu vede Team/Settings/Content Library.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0011 - Schimbare parola din My account (P1)

- Preconditii: User autentificat.
- Pasi:
  - Mergi la My account.
  - Introdu parola curenta, parola noua si confirmare.
  - Salveaza.
- Rezultat asteptat: Parola se schimba; login vechi esueaza, login nou reuseste.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### AUTH-0012 - Preferinte profil (P1)

- Preconditii: User autentificat.
- Pasi:
  - Schimba nume/profil/preferinte tema sau UI.
  - Salveaza si reincarca pagina.
- Rezultat asteptat: Modificarile persista si nu afecteaza alte conturi.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 2. Roluri si permisiuni

Aceste teste previn scurgerile de date intre workspace-uri, proiecte si roluri.

### PERM-0001 - Owner poate crea proiecte (P0)

- Preconditii: User cu rol owner in workspace.
- Pasi:
  - Deschide Dashboard si Projects.
  - Cauta butonul New project.
  - Creeaza proiect.
- Rezultat asteptat: Owner vede butonul si poate crea daca limita abonamentului permite.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0002 - Admin nu poate crea proiecte (P0)

- Preconditii: User admin in workspace.
- Pasi:
  - Deschide Dashboard si Projects.
  - Cauta New project si Duplicate project.
- Rezultat asteptat: Admin nu vede butoanele de creare/duplicare proiect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0003 - Member nu poate crea proiecte (P0)

- Preconditii: User member in workspace.
- Pasi:
  - Deschide Dashboard si Projects.
  - Cauta New project si Duplicate project.
- Rezultat asteptat: Member nu vede butoanele si nu poate accesa /projects/create manual.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0004 - Viewer citeste fara editare (P0)

- Preconditii: User viewer in workspace.
- Pasi:
  - Deschide un proiect.
  - Incearca sa modifici buget, participanti, documente, tasks.
- Rezultat asteptat: Viewer vede continutul permis dar nu poate salva modificari.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0005 - Project-only vede doar proiectul alocat (P0)

- Preconditii: User project-only cu un proiect asignat.
- Pasi:
  - Deschide Projects.
  - Cauta alte proiecte din workspace.
  - Acceseaza URL direct al altui proiect.
- Rezultat asteptat: Lista si rutele directe blocheaza proiectele nealocate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0006 - Project-only nu vede setari workspace (P0)

- Preconditii: User project-only.
- Pasi:
  - Verifica meniul lateral.
  - Incearca URL-uri Team, Document templates, Workspace data, Reports.
- Rezultat asteptat: Modulele workspace-wide sunt ascunse sau blocate controlat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0007 - Admin platforma nu vede module client inutile (P0)

- Preconditii: Cont platform_admin.
- Pasi:
  - Autentifica-te pe platform admin.
  - Verifica meniul.
- Rezultat asteptat: Apar doar module de management platforma, nu module operationale de proiect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0008 - Platform owner are acces complet admin (P0)

- Preconditii: Cont platform_owner.
- Pasi:
  - Verifica users, workspaces, subscriptions, announcements, audit, impersonation.
- Rezultat asteptat: Owner are actiuni complete, inclusiv gestionare admini.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0009 - Platform admin nu modifica platform owner/admin (P1)

- Preconditii: Cont platform_admin.
- Pasi:
  - Deschide alt admin si platform owner.
  - Cauta edit/reset/delete/suspend.
- Rezultat asteptat: Actiunile critice sunt indisponibile pentru admin, disponibile doar ownerului unde e cazul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0010 - Workspace suspendat/read-only (P0)

- Preconditii: Workspace expirat/suspendat.
- Pasi:
  - Intra ca owner/member.
  - Incearca salvare proiect, upload, invitatii.
- Rezultat asteptat: Platforma blocheaza modificarile si explica situatia.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0011 - Date intre workspace-uri (P1)

- Preconditii: User in doua workspace-uri.
- Pasi:
  - Schimba workspace-ul.
  - Cauta proiecte/participanti/documente din celalalt workspace.
- Rezultat asteptat: Nu exista scurgeri de date intre workspace-uri.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PERM-0012 - Limitare abonament proiecte (P1)

- Preconditii: Workspace cu limita atinsa.
- Pasi:
  - Intra ca owner.
  - Cauta New project si Duplicate project.
- Rezultat asteptat: Crearea este blocata sau ascunsa cu mesaj clar.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 3. Navigatie, layout si ergonomie

Se verifica meniul, tema, antetul, popoverele si comportamentul general al UI-ului.

### UI-0001 - Sidebar desktop inchis/deschis (P0)

- Preconditii: User owner in workspace.
- Pasi:
  - Comuta sidebar-ul inchis/deschis.
  - Parcurge toate iconitele.
- Rezultat asteptat: Sidebar-ul nu necesita scroll vertical si iconitele au dimensiune proportionala.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0002 - Sidebar admin (P1)

- Preconditii: Cont platform_admin/owner.
- Pasi:
  - Repeta testul sidebar in admin.
  - Verifica gruparea Platform management, Billing & access, Audit & operations.
- Rezultat asteptat: Meniul admin este compact si fara module irelevante.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0003 - Tema dark/light (P1)

- Preconditii: User autentificat.
- Pasi:
  - Comuta tema din dreapta sus.
  - Verifica dashboard, proiect, formulare, modale, PDF previews.
- Rezultat asteptat: Contrastul ramane bun, fara texte invizibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0004 - Tooltipuri langa marginea dreapta (P1)

- Preconditii: Pagina cu semne de intrebare.
- Pasi:
  - Apasa tooltipuri aproape de marginea dreapta.
  - Repeta pe mobile.
- Rezultat asteptat: Popoverul se deschide in ecran, nu iese in afara.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0005 - Antet anunturi platforma (P1)

- Preconditii: Exista announcement activ.
- Pasi:
  - Deschide dashboard ca user.
  - Verifica bannerele.
  - Inchide daca exista actiune.
- Rezultat asteptat: Anuntul este vizibil, lizibil si nu acopera continutul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0006 - Responsive mobile (P1)

- Preconditii: Viewport 390px.
- Pasi:
  - Navigheaza dashboard, projects, writing, participants, documents.
- Rezultat asteptat: Nu exista scroll orizontal inutil, butoanele sunt accesibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0007 - Stari empty/loading/error (P2)

- Preconditii: Workspace nou sau filtrari fara rezultate.
- Pasi:
  - Deschide module fara date.
  - Aplica filtre imposibile.
  - Reincarca pagina.
- Rezultat asteptat: Empty states sunt clare, nu apar pagini Laravel brute.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0008 - Accesibilitate de baza (P1)

- Preconditii: Keyboard only.
- Pasi:
  - Navigheaza cu Tab prin login, dashboard, proiect, modale.
  - Inchide modale cu Escape.
- Rezultat asteptat: Focus vizibil, modalele sunt utilizabile fara mouse.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0009 - Confirmari destructive (P1)

- Preconditii: User cu drepturi edit.
- Pasi:
  - Incearca delete participant, delete expense, archive project, delete account in admin.
- Rezultat asteptat: Actiunile destructive cer confirmare si textul explica efectul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### UI-0010 - Texte lungi (P2)

- Preconditii: Proiect/organizatie/email cu nume lung.
- Pasi:
  - Creeaza date cu nume foarte lungi.
  - Verifica carduri, dropdown, tabele, PDF.
- Rezultat asteptat: Textele se taie elegant sau trec pe rand fara ruperea layoutului.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 4. Dashboard workspace

Dashboard-ul trebuie sa fie prima vedere operationala clara: proiecte active, prioritati, milestone-uri si quick actions.

### DASH-0001 - Incarcare dashboard (P0)

- Preconditii: Workspace cu date.
- Pasi:
  - Deschide /app/{workspace}.
  - Verifica widgeturile principale.
- Rezultat asteptat: Nu apar 500/419; proiectele, bugetele si milestone-urile sunt corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0002 - Active projects stat (P1)

- Preconditii: Exista proiecte approved/active/completed.
- Pasi:
  - Compara numarul din dashboard cu lista Projects.
- Rezultat asteptat: Statistica exclude proiecte respinse/completate unde este cazul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0003 - Approved funding si spent (P1)

- Preconditii: Exista bugete si cheltuieli.
- Pasi:
  - Compara suma din dashboard cu bugetele proiectelor.
- Rezultat asteptat: Valorile sunt rotunjite corect si nu includ proiecte inaccesibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0004 - Needs attention (P1)

- Preconditii: Participant incomplet, expense fara evidence, task overdue.
- Pasi:
  - Creeaza aceste situatii.
  - Verifica lista Needs attention.
- Rezultat asteptat: Apar alertele relevante cu link catre modulul corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0005 - Readiness (P1)

- Preconditii: Proiect in writing cu sectiuni incomplete.
- Pasi:
  - Verifica cardul readiness.
  - Apasa linkul next action.
- Rezultat asteptat: Linkul duce la Writing/Budget/Participants/Documents dupa caz.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0006 - Upcoming milestones (P1)

- Preconditii: Proiect cu date in urmatoarele 60 zile.
- Pasi:
  - Verifica milestone-urile.
  - Schimba datele proiectului si revino.
- Rezultat asteptat: Milestone-urile se actualizeaza corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0007 - Quick action New project doar owner (P0)

- Preconditii: Owner/admin/member.
- Pasi:
  - Compara dashboard pentru fiecare rol.
- Rezultat asteptat: New project apare doar la owner.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0008 - Quick actions contextuale (P1)

- Preconditii: Exista proiect writing si active.
- Pasi:
  - Apasa Continue application, Manage expenses, Add participants, Create documents.
- Rezultat asteptat: Fiecare link deschide modulul corect pentru proiectul corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DASH-0009 - Project-only dashboard (P1)

- Preconditii: User project-only.
- Pasi:
  - Deschide dashboard.
  - Verifica proiecte si quick actions.
- Rezultat asteptat: Dashboard-ul nu afiseaza date din proiecte nealocate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 5. Projects - lista, creare, duplicare, arhivare

Acopera lista de proiecte, arhivarea si fluxul de proiect fara template.

### PROJ-0001 - Lista proiecte active (P0)

- Preconditii: Workspace cu minimum 2 proiecte.
- Pasi:
  - Deschide Projects.
  - Verifica ordinea si cardurile.
- Rezultat asteptat: Proiectele active/approved apar primele; buget si participanti afisati corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0002 - Creare proiect cu template (P0)

- Preconditii: Owner.
- Pasi:
  - Apasa New project.
  - Completeaza nume, template, date, organizatii, buget.
  - Salveaza.
- Rezultat asteptat: Proiectul se creeaza si redirectioneaza catre Overview.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0003 - Creare proiect fara template (P0)

- Preconditii: Owner.
- Pasi:
  - Apasa New project.
  - Lasa Application template gol.
  - Salveaza.
- Rezultat asteptat: Proiectul se creeaza fara sectiuni Writing obligatorii si poate fi administrat operational.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0004 - Validari creare proiect (P1)

- Preconditii: Owner.
- Pasi:
  - Incearca salvare fara nume, date inversate, buget negativ.
- Rezultat asteptat: Campurile invalide sunt marcate clar si nu se salveaza date gresite.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0005 - Duplicate project (P0)

- Preconditii: Owner, proiect sursa cu buget/application/partners.
- Pasi:
  - Apasa Duplicate project.
  - Alege sursa, bifeaza/debifeaza optiuni.
  - Creeaza copia.
- Rezultat asteptat: Copia nu include participanti, cheltuieli, fisiere, grant ref sau date vechi.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0006 - Duplicate ascuns non-owner (P0)

- Preconditii: Admin/member/viewer.
- Pasi:
  - Deschide Projects.
  - Cauta Duplicate project.
- Rezultat asteptat: Butonul nu apare si ruta nu permite crearea.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0007 - Archived projects (P0)

- Preconditii: Exista proiect arhivat.
- Pasi:
  - Deschide Projects.
  - Apasa Archived projects.
  - Revino la Active projects.
- Rezultat asteptat: Lista comuta corect si izoleaza proiectele arhivate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0008 - Archive numai din Settings (P0)

- Preconditii: User cu drepturi edit.
- Pasi:
  - Deschide Project Overview.
  - Verifica lipsa butonului Archive.
  - Mergi la Settings - More actions - Archive project.
- Rezultat asteptat: Overview nu are archive; Settings arhiveaza cu confirmare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0009 - Restore proiect (P0)

- Preconditii: Proiect arhivat.
- Pasi:
  - Projects - Archived projects.
  - Apasa Restore.
  - Confirma.
- Rezultat asteptat: Proiectul revine in lista activa cu datele pastrate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0010 - Cautare URL manual proiect arhivat (P1)

- Preconditii: Proiect arhivat.
- Pasi:
  - Deschide URL direct al proiectului arhivat.
- Rezultat asteptat: Accesul este blocat sau redirectionat controlat, fara 500.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0011 - Card proiect restricted (P1)

- Preconditii: Proiect restricted.
- Pasi:
  - Deschide Projects ca owner si ca user selectat/ne-selectat.
- Rezultat asteptat: Iconita lock apare unde trebuie si accesul respecta selectia.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PROJ-0012 - Limita abonament proiecte (P1)

- Preconditii: Workspace la limita.
- Pasi:
  - Intra ca owner.
  - Verifica New project si Duplicate.
- Rezultat asteptat: Crearea este blocata clar.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 6. Project Overview

Overview-ul gestioneaza starea proiectului, readiness, taskuri si accesul proiectului.

### OVR-0001 - Deschidere overview (P0)

- Preconditii: Proiect existent.
- Pasi:
  - Deschide cardul proiectului.
  - Verifica subnavigatia Overview/Application/Budget/Participants/Documents/Settings.
- Rezultat asteptat: Pagina incarca fara erori si datele cheie sunt coerente.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0002 - Tranzitii status (P1)

- Preconditii: Proiect writing.
- Pasi:
  - Apasa tranzitiile disponibile.
  - Verifica modal readiness daca exista blocaje.
- Rezultat asteptat: Statusul se schimba doar cand conditiile sunt indeplinite.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0003 - Readiness panel (P1)

- Preconditii: Proiect incomplet.
- Pasi:
  - Verifica scorul readiness si itemurile critice/warning.
  - Apasa recomandarea urmatoare.
- Rezultat asteptat: Recomandarea deschide modulul corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0004 - Project access workspace (P0)

- Preconditii: Owner/admin.
- Pasi:
  - Project access.
  - Seteaza Everyone in workspace.
  - Salveaza.
- Rezultat asteptat: Membrii workspace vad proiectul; project-only selectati raman daca exista.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0005 - Project access restricted (P0)

- Preconditii: Owner/admin, membri existenti.
- Pasi:
  - Project access.
  - Alege restricted.
  - Selecteaza un viewer.
  - Salveaza.
- Rezultat asteptat: Doar owner/admin si viewerul selectat vad proiectul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0006 - Invite project-only (P0)

- Preconditii: Owner/admin.
- Pasi:
  - Project access.
  - Introdu email nou la invite project-only.
  - Salveaza.
  - Accepta invitatia.
- Rezultat asteptat: Invitatul vede doar acest proiect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0007 - Add task (P1)

- Preconditii: User manager/collaborator proiect.
- Pasi:
  - Apasa Add task.
  - Completeaza titlu, prioritate, due date, assignee.
  - Salveaza.
- Rezultat asteptat: Taskul apare in overview si in My Tasks pentru assignee.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0008 - Edit/complete/reopen task (P1)

- Preconditii: Exista task.
- Pasi:
  - Editeaza taskul.
  - Marcheaza complete.
  - Redeschide.
- Rezultat asteptat: Statusul si notificarile se actualizeaza fara duplicari.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0009 - Archive absent in Overview (P0)

- Preconditii: User manager.
- Pasi:
  - Deschide Overview.
  - Cauta Archive project in header si More actions.
- Rezultat asteptat: Nu exista buton de arhivare in Overview.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### OVR-0010 - Project-only poate lucra taskuri (P1)

- Preconditii: Project-only asignat.
- Pasi:
  - Deschide proiectul.
  - Creeaza/editeaza task daca are drept collaborator.
  - Verifica My Tasks.
- Rezultat asteptat: Poate lucra in proiectul alocat fara acces la restul workspace-ului.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 7. Writing si application templates

Se verifica template-urile oficiale, intrebarile, editorul, tabelele si exporturile de redactare.

### WRIT-0001 - Template manager search (P0)

- Preconditii: Proiect cu sau fara template.
- Pasi:
  - Deschide Application.
  - Cauta template dupa KA si nume.
  - Schimba template.
- Rezultat asteptat: Lista este filtrabila si intrebarile se schimba complet dupa template.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0002 - Intrebari oficiale per template (P0)

- Preconditii: Template KA151/KA152/KA153/KA210/KA220 sau disponibile.
- Pasi:
  - Selecteaza fiecare template.
  - Compara intrebarile cu ghidul/formularul oficial.
- Rezultat asteptat: Intrebarile sunt identice ca formulare cerute si nu raman intrebari din alt template.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0003 - KA152-YOU complet (P0)

- Preconditii: Template KA152-YOU.
- Pasi:
  - Verifica toate sectiunile si intrebarile.
  - Completeaza raspunsuri scurte si lungi.
- Rezultat asteptat: Nu lipsesc intrebari; textul se vede complet in lista si editor.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0004 - Scroll coloane separate (P1)

- Preconditii: Application deschis.
- Pasi:
  - Da scroll in stanga si dreapta separat.
  - Verifica finalul editorului.
- Rezultat asteptat: Nu trebuie sa parcurgi toate intrebarile ca sa ajungi jos in editor.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0005 - Autosave/manual save (P1)

- Preconditii: Sectiune deschisa.
- Pasi:
  - Scrie text.
  - Schimba sectiunea.
  - Reincarca pagina.
- Rezultat asteptat: Textul ramane salvat sau exista feedback clar de salvare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0006 - Character limits (P1)

- Preconditii: Sectiune cu limita.
- Pasi:
  - Depaseste limita.
  - Revino sub limita.
- Rezultat asteptat: Counterul si validarile sunt corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0007 - Tabele standard (P1)

- Preconditii: Template cu tabele.
- Pasi:
  - Deschide sectiunile de tabele.
  - Adauga randuri, sterge randuri, completeaza celule.
- Rezultat asteptat: Tabelele apar in lista/sectiune, se salveaza si nu rup layoutul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0008 - Content Library insert (P1)

- Preconditii: Exista bloc privat.
- Pasi:
  - In editor cauta bloc.
  - Insereaza text.
  - Editeaza apoi.
- Rezultat asteptat: Blocul se insereaza corect si nu suprascrie raspunsuri existente.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0009 - Public Library insert/import (P1)

- Preconditii: Exista bloc public.
- Pasi:
  - Cauta bloc public.
  - Importa sau insereaza.
- Rezultat asteptat: Textul ajunge in proiect sau biblioteca privata dupa actiunea aleasa.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0010 - Supervisor/review mode (P1)

- Preconditii: Functia disponibila.
- Pasi:
  - Deschide supervising/review.
  - Adauga feedback si navigheaza intre sectiuni.
- Rezultat asteptat: Feedbackul este legat de sectiunea corecta si lizibil.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0011 - Export application (P1)

- Preconditii: Proiect cu raspunsuri.
- Pasi:
  - Genereaza/exporta aplicatia daca exista actiune.
  - Deschide fisierul.
- Rezultat asteptat: Raspunsurile si tabelele sunt in ordine si fara text taiat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### WRIT-0012 - Template fara aplicatie (P2)

- Preconditii: Proiect fara template.
- Pasi:
  - Deschide Application.
  - Alege template ulterior.
  - Sterge/curata template daca este posibil.
- Rezultat asteptat: Proiectul operational nu este fortat sa aiba writing.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 8. Estimate si Budget

Include estimare, buget de implementare, cosuri/baskets, transferuri, cheltuieli si exporturi.

### BUD-0001 - Estimate in writing (P0)

- Preconditii: Proiect writing.
- Pasi:
  - Deschide Estimate.
  - Completeaza grant items si sume.
- Rezultat asteptat: Totalurile se calculeaza corect si raman salvate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0002 - Budget in management (P0)

- Preconditii: Proiect active/approved.
- Pasi:
  - Deschide Budget.
  - Verifica cosurile implicite.
- Rezultat asteptat: Baskets apar cu alocari, spent si remaining corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0003 - Add expense (P0)

- Preconditii: Basket existent.
- Pasi:
  - Apasa Add expense.
  - Completeaza descriere, data, suma, valuta, rata, furnizor.
  - Salveaza.
- Rezultat asteptat: Cheltuiala apare in basket si totalurile se actualizeaza.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0004 - Upload evidence (P1)

- Preconditii: Cheltuiala existenta.
- Pasi:
  - Incarca fisier justificativ.
  - Descarca fisierul.
  - Sterge atasamentul.
- Rezultat asteptat: Upload/download/delete functioneaza si fisierul nu se pierde.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0005 - Currency recalculation (P1)

- Preconditii: Valute configurate.
- Pasi:
  - Creeaza expense in alta valuta.
  - Modifica exchange rate global.
  - Verifica expense.
- Rezultat asteptat: Cheltuielile se actualizeaza conform regulilor stabilite.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0006 - Edit/delete expense (P1)

- Preconditii: Expense existent.
- Pasi:
  - Editeaza campuri.
  - Sterge expense cu confirmare.
- Rezultat asteptat: Totalurile se recalculeaza si auditul nu produce erori.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0007 - Notes expense (P1)

- Preconditii: Expense existent.
- Pasi:
  - Deschide notes.
  - Adauga text.
  - Salveaza si redeschide.
- Rezultat asteptat: Notitele persista.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0008 - Create/edit/delete basket (P1)

- Preconditii: User manager.
- Pasi:
  - Adauga basket custom.
  - Schimba nume, emoji, culoare.
  - Sterge basket.
- Rezultat asteptat: Basketul se salveaza, iar stergerea cere confirmare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0009 - Transfer budget (P1)

- Preconditii: Doua baskets.
- Pasi:
  - Apasa Transfer budget.
  - Alege sursa/destinatie/suma.
  - Salveaza.
  - Reverse transfer.
- Rezultat asteptat: Alocarile se modifica si reverse restaureaza corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0010 - Export budget PDF (P0)

- Preconditii: Buget cu expenses.
- Pasi:
  - Apasa Export PDF.
  - Deschide fisierul.
- Rezultat asteptat: PDF-ul include cheltuieli, cosuri, sume si ordonare corecta.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0011 - Document expense report by basket/order (P1)

- Preconditii: Exista expenses in mai multe cosuri.
- Pasi:
  - Genereaza raport de cheltuieli cu ordonari diferite.
  - Compara PDF.
- Rezultat asteptat: Documentul respecta optiunile de sortare/grupare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### BUD-0012 - Civil convention flag pe expense (P1)

- Preconditii: Expense eligibila.
- Pasi:
  - Bifeaza civil convention.
  - Verifica Documents.
- Rezultat asteptat: Conventia apare in lista din Documents si poate fi generata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 9. Participants

Se verifica adaugarea, import/export, documentele personale, attendance list si validarea datelor.

### PART-0001 - Add participant (P0)

- Preconditii: Proiect existent.
- Pasi:
  - Apasa Add participant.
  - Completeaza nume, prenume, email, telefon, tara, organizatie, rol, date personale.
  - Salveaza.
- Rezultat asteptat: Participantul apare in lista si este sortabil/filtrabil.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0002 - Edit participant (P1)

- Preconditii: Participant existent.
- Pasi:
  - Deschide edit.
  - Schimba email/telefon/organizatie.
  - Salveaza.
- Rezultat asteptat: Datele se actualizeaza in lista si exporturi.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0003 - Delete participant (P1)

- Preconditii: Participant existent.
- Pasi:
  - Apasa delete.
  - Confirma.
- Rezultat asteptat: Participantul dispare si atasamentele sunt gestionate corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0004 - Filters (P1)

- Preconditii: Mai multi participanti.
- Pasi:
  - Deschide filtre.
  - Filtreaza dupa tara, organizatie, rol, documente incomplete.
  - Clear filters.
- Rezultat asteptat: Rezultatele sunt corecte si filtrele se curata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0005 - Export CSV (P0)

- Preconditii: Participanti existenti.
- Pasi:
  - Apasa Export CSV.
  - Deschide fisierul.
- Rezultat asteptat: Formatul are coloane complete si datele sunt corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0006 - Import CSV exportat nemodificat (P0)

- Preconditii: Ai CSV exportat.
- Pasi:
  - Importa acelasi CSV fara modificari.
- Rezultat asteptat: Importul trece fara eroare de format data.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0007 - Import CSV modificat (P0)

- Preconditii: Ai CSV exportat.
- Pasi:
  - Modifica un participant si adauga unul nou.
  - Importa.
- Rezultat asteptat: Datele se actualizeaza/adauga fara duplicate neasteptate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0008 - Format data CSV (P1)

- Preconditii: CSV cu date in format exportat.
- Pasi:
  - Verifica formatele zi/luna/an si ISO.
  - Importa ambele daca sunt acceptate.
- Rezultat asteptat: Platforma accepta formatul pe care il exporta.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0009 - Attendance list PDF (P0)

- Preconditii: Participanti grupati pe asociatii.
- Pasi:
  - Apasa Attendance list.
  - Genereaza PDF.
  - Deschide fisierul.
- Rezultat asteptat: PDF-ul nu are pagina goala, e landscape daca trebuie, fiecare asociatie incepe pe pagina noua, participanti alfabetici.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0010 - Upload document participant (P1)

- Preconditii: Participant deschis.
- Pasi:
  - Incarca CI/passport/CV/other.
  - Descarca si sterge.
- Rezultat asteptat: Fisierele sunt asociate participantului corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0011 - Minori si varsta (P1)

- Preconditii: Participant cu birth date.
- Pasi:
  - Schimba data mobilitatii.
  - Verifica indicator minor/required docs.
- Rezultat asteptat: Calculul varstei foloseste data mobilitatii.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### PART-0012 - Viewer read-only (P1)

- Preconditii: User viewer.
- Pasi:
  - Deschide Participants.
  - Cauta add/import/delete/upload.
- Rezultat asteptat: Actiunile de modificare sunt ascunse sau blocate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 10. Documents

Acopera generare, upload semnat, checklist, partner documents, conventii civile si documente finale.

### DOC-0001 - Lista Documents layout (P0)

- Preconditii: Proiect cu documente.
- Pasi:
  - Deschide Documents.
  - Verifica taburile Files, Civil conventions, Checklist.
- Rezultat asteptat: Taburile sunt vizibile fara header aglomerat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0002 - Add document upload (P0)

- Preconditii: User manager.
- Pasi:
  - Apasa Add document.
  - Incarca PDF/DOCX.
  - Seteaza titlu/tip.
- Rezultat asteptat: Documentul apare in Files si poate fi descarcat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0003 - Generate attendance from Documents (P0)

- Preconditii: Participanti existenti.
- Pasi:
  - Genereaza attendance list din Documents.
  - Compara cu butonul din Participants.
- Rezultat asteptat: Ambele fluxuri duc la acelasi rezultat corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0004 - Upload signed copy (P0)

- Preconditii: Document generat.
- Pasi:
  - Apasa View pending signature.
  - Incarca signed copy.
  - Verifica butoane.
- Rezultat asteptat: Documentul devine signed; Replace signed copy nu ramane buton principal daca exista in meniu.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0005 - Replace signed copy din meniu (P1)

- Preconditii: Document signed.
- Pasi:
  - Deschide meniul cu trei puncte.
  - Alege replace.
  - Incarca alt fisier.
- Rezultat asteptat: Fisierul semnat se inlocuieste corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0006 - Expense report oficial (P0)

- Preconditii: Cheltuieli existente.
- Pasi:
  - Genereaza raport de cheltuieli.
  - Alege sortare/grupare pe cosuri daca exista.
  - Deschide PDF.
- Rezultat asteptat: Raportul este oficial, complet si valorile coincid cu Budget.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0007 - Civil convention din expense (P0)

- Preconditii: Expense bifata civil convention.
- Pasi:
  - Deschide Civil conventions.
  - Completeaza datele persoanei.
  - Genereaza document.
- Rezultat asteptat: Conventia se genereaza in engleza cu rata de impozitare din project settings.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0008 - Civil convention validation (P1)

- Preconditii: Conventie necompletata.
- Pasi:
  - Incearca generare fara campuri obligatorii.
- Rezultat asteptat: Apar validari clare si nu se genereaza document incomplet.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0009 - Partner documents duplicate (P1)

- Preconditii: Mai multe organizatii.
- Pasi:
  - Deschide partner documents.
  - Verifica Roots/Scoala de Jocuri si alte nume.
- Rezultat asteptat: Nu apar duplicate nejustificate pentru aceeasi organizatie.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0010 - Checklist (P1)

- Preconditii: Proiect cu documente lipsa.
- Pasi:
  - Deschide Checklist.
  - Bifeaza/manual upload daca exista.
  - Genereaza documente lipsa.
- Rezultat asteptat: Checklistul reflecta statusul real.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0011 - Delete document (P1)

- Preconditii: Document uploadat.
- Pasi:
  - Sterge document.
  - Confirma.
- Rezultat asteptat: Documentul dispare si fisierul nu ramane accesibil public.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0012 - Viewer documents (P1)

- Preconditii: User viewer.
- Pasi:
  - Deschide Documents.
  - Incearca generate/upload/delete.
- Rezultat asteptat: Viewer poate descarca, dar nu modifica.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0013 - Download generated files (P1)

- Preconditii: Documente generate.
- Pasi:
  - Descarca fiecare tip generat.
  - Deschide local.
- Rezultat asteptat: Fisierele nu sunt corupte si au nume clare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DOC-0014 - Document logo/signatory (P1)

- Preconditii: Document templates configurate.
- Pasi:
  - Seteaza logo si signatory.
  - Genereaza document.
- Rezultat asteptat: Logo si semnatar apar corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 11. Mobility si arhiva finala

Zona Mobility centralizeaza raportul de mobilitate, materiale, fisiere realizate si exportul final.

### MOB-0001 - Mobility page load (P0)

- Preconditii: Proiect existent.
- Pasi:
  - Deschide Mobility.
  - Verifica sectiunile report, documents/materials, final archive.
- Rezultat asteptat: Pagina incarca fara erori si explica scopul zonei.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0002 - Save mobility report (P0)

- Preconditii: User manager.
- Pasi:
  - Scrie raport de mobilitate/diseminare.
  - Salveaza.
  - Reincarca.
- Rezultat asteptat: Raportul persista.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0003 - Upload mobility document (P1)

- Preconditii: Fisier local.
- Pasi:
  - Incarca planse/materiale/foto/documente.
  - Seteaza categorie si descriere.
- Rezultat asteptat: Fisierul apare in lista si se descarca.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0004 - Delete mobility document (P1)

- Preconditii: Document existent.
- Pasi:
  - Sterge cu confirmare.
- Rezultat asteptat: Fisierul dispare si nu mai este accesibil.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0005 - Final archive ZIP (P0)

- Preconditii: Proiect cu participanti, budget, documents, mobility files.
- Pasi:
  - Apasa Download final archive.
  - Dezarhiveaza ZIP.
- Rezultat asteptat: Arhiva contine foldere ordonate si toate datele/fisierele relevante.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0006 - Ordine arhiva finala (P1)

- Preconditii: ZIP generat.
- Pasi:
  - Verifica structura folderelor si denumirile.
  - Cauta fisiere lipsa.
- Rezultat asteptat: Structura este logica pentru audit final.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MOB-0007 - Project-only Mobility (P1)

- Preconditii: Project-only collaborator.
- Pasi:
  - Deschide Mobility pentru proiectul alocat.
  - Incearca upload si download.
- Rezultat asteptat: Accesul este permis doar pentru proiectul alocat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 12. Diseminare

Diseminarea trebuie verificata separat pe organizator si inclusa in arhiva finala.

### DIS-0001 - Sectiune diseminare vizibila (P0)

- Preconditii: Proiect cu organizatii.
- Pasi:
  - Gaseste sectiunea de diseminare.
  - Verifica organizatorii/partenerii.
- Rezultat asteptat: Fiecare organizator are zona proprie.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DIS-0002 - Upload dovada pe organizator (P0)

- Preconditii: Fisier justificativ.
- Pasi:
  - Alege organizator.
  - Incarca dovada diseminare.
  - Adauga descriere/data.
- Rezultat asteptat: Dovada se leaga de organizatorul corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DIS-0003 - Raport diseminare (P0)

- Preconditii: Organizator existent.
- Pasi:
  - Completeaza campul raport.
  - Salveaza si reincarca.
- Rezultat asteptat: Raportul persista si nu se amesteca intre organizatori.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DIS-0004 - Multiple fisiere (P1)

- Preconditii: Mai multe dovezi.
- Pasi:
  - Incarca 3 fisiere la organizatori diferiti.
  - Descarca si sterge unul.
- Rezultat asteptat: Listele si actiunile sunt corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### DIS-0005 - Includere in final archive (P0)

- Preconditii: Dovezi diseminare existente.
- Pasi:
  - Genereaza final archive.
  - Cauta folderul de diseminare.
- Rezultat asteptat: Dovezile si raportul sunt incluse ordonat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 13. Planning tools: Calculator, Libraries, Currencies, Calendar, Tasks

Modulele de planificare trebuie sa fie utile, clare si izolate pe workspace.

### TOOLS-0001 - Individual Support Calculator basic (P0)

- Preconditii: Date calculator disponibile.
- Pasi:
  - Completeaza tara, durata, fluxuri.
  - Verifica totalurile.
- Rezultat asteptat: Calculul este corect si explicatiile sunt clare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0002 - Calculator tooltips (P1)

- Preconditii: Calculator deschis.
- Pasi:
  - Apasa toate semnele de intrebare.
  - Verifica pozitionare si text.
- Rezultat asteptat: Tooltipurile explica suficient si nu ies din ecran.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0003 - Calculator export (P1)

- Preconditii: Calcul complet.
- Pasi:
  - Apasa Export.
  - Deschide fisierul.
- Rezultat asteptat: Exportul contine valorile si parametrii calculului.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0004 - Save/load/delete calculation (P1)

- Preconditii: Calcul complet.
- Pasi:
  - Save.
  - Load.
  - Delete cu confirmare.
- Rezultat asteptat: Calculele salvate sunt private workspace si se gestioneaza corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0005 - Writing Library CRUD (P0)

- Preconditii: User manager.
- Pasi:
  - Creeaza bloc privat.
  - Editeaza, cauta, duplica, sterge.
- Rezultat asteptat: Blocurile sunt gestionate fara pierderi de continut.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0006 - Publish/import public block (P1)

- Preconditii: Bloc privat/public.
- Pasi:
  - Publica bloc.
  - Importa in alt workspace.
  - Raporteaza bloc public daca exista.
- Rezultat asteptat: Fluxul public/private este clar si nu expune date sensibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0007 - Currencies update (P0)

- Preconditii: Valute existente.
- Pasi:
  - Adauga valuta/rata.
  - Modifica rata.
  - Verifica buget/expenses.
- Rezultat asteptat: Rata se salveaza si efectul asupra cheltuielilor este consistent.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0008 - Calendar events (P1)

- Preconditii: Proiecte cu date si tasks.
- Pasi:
  - Deschide Calendar.
  - Verifica evenimente.
  - Apasa eveniment.
- Rezultat asteptat: Evenimentele duc la proiect/task corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0009 - My Tasks list (P0)

- Preconditii: Task asignat userului.
- Pasi:
  - Deschide My Tasks.
  - Filtreaza open/completed/overdue.
  - Cauta.
- Rezultat asteptat: Lista contine doar taskurile userului din proiecte accesibile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0010 - Complete task from My Tasks (P1)

- Preconditii: Task open.
- Pasi:
  - Apasa checkbox complete.
  - Reopen.
- Rezultat asteptat: Statusul se sincronizeaza cu Overview.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### TOOLS-0011 - Portfolio reports (P1)

- Preconditii: Mai multe proiecte.
- Pasi:
  - Deschide Reports.
  - Filtreaza status/date.
  - Export CSV.
- Rezultat asteptat: CSV-ul contine doar proiectele accesibile si coloanele corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 14. Workspace settings si cont

Setarile definesc echipa, documentele, backupurile, notificarile si preferintele contului.

### SET-0001 - Workspace profile (P0)

- Preconditii: Owner/admin.
- Pasi:
  - Deschide Workspace settings/profile.
  - Schimba nume/logo/date billing daca exista.
  - Salveaza.
- Rezultat asteptat: Modificarile apar in documente si interfata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0002 - Team invite (P0)

- Preconditii: Owner/admin.
- Pasi:
  - Deschide Team.
  - Invita admin/member/viewer.
  - Accepta emailul.
- Rezultat asteptat: Rolul invitatului este aplicat corect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0003 - Team role update (P1)

- Preconditii: Membru existent.
- Pasi:
  - Schimba rol member/viewer/admin.
  - Reautentifica userul.
- Rezultat asteptat: Permisiunile se actualizeaza.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0004 - Remove member (P1)

- Preconditii: Membru non-owner.
- Pasi:
  - Remove member cu confirmare.
  - Incearca login/access.
- Rezultat asteptat: Userul nu mai are acces la workspace.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0005 - Document templates (P0)

- Preconditii: Owner/admin.
- Pasi:
  - Seteaza signatory, logo, texte template.
  - Genereaza document.
- Rezultat asteptat: Template-ul afecteaza documentele generate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0006 - Remove logo (P1)

- Preconditii: Logo existent.
- Pasi:
  - Apasa Remove logo.
  - Genereaza document.
- Rezultat asteptat: Logo-ul nu mai apare.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0007 - Full workspace backup (P0)

- Preconditii: Workspace cu date.
- Pasi:
  - Deschide Backup & exports.
  - Download ZIP.
  - Dezarhiveaza.
- Rezultat asteptat: Backupul include date active/arhivate si fisiere, fara parole/tokenuri.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0008 - Notification preferences (P1)

- Preconditii: User autentificat.
- Pasi:
  - Schimba preferinte notificari.
  - Salveaza.
  - Declanseaza task/invitatie.
- Rezultat asteptat: Preferintele sunt respectate unde exista notificari.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0009 - Account profile (P1)

- Preconditii: My account.
- Pasi:
  - Schimba nume si preferinte.
  - Salveaza.
- Rezultat asteptat: Datele contului se actualizeaza.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0010 - Workspace switcher (P1)

- Preconditii: User in doua workspace-uri.
- Pasi:
  - Din My account sau selector, deschide alt workspace.
- Rezultat asteptat: Contextul se schimba fara scurgeri de date.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SET-0011 - Plan display (P1)

- Preconditii: Workspace cu plan setat.
- Pasi:
  - Deschide My account/subscription.
  - Verifica plan/limite.
- Rezultat asteptat: Planul si limitele afisate corespund admin panel.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 15. Admin platform

Aceste teste sunt pentru platform_owner si platform_admin. Confirma controlul conturilor, abonamentelor, anunturilor si auditului.

### ADM-0001 - Admin dashboard (P0)

- Preconditii: platform_admin/owner.
- Pasi:
  - Deschide platform dashboard.
  - Verifica stats, operations, alerts.
- Rezultat asteptat: Dashboard-ul admin nu contine module client inutile.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0002 - Users list (P0)

- Preconditii: Admin.
- Pasi:
  - Deschide Users.
  - Filtreaza active/suspended/archived.
  - Cauta email.
- Rezultat asteptat: Lista afiseaza date relevante: plan, workspace-uri, proiecte, status.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0003 - View user details (P0)

- Preconditii: User existent.
- Pasi:
  - Deschide user.
  - Verifica memberships, notes, status, support data.
- Rezultat asteptat: Datele sunt complete si nu exista actiuni periculoase expuse gresit.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0004 - Edit user (P0)

- Preconditii: Permisiune owner/admin conform regulilor.
- Pasi:
  - Deschide edit.
  - Schimba date non-critice.
  - Salveaza.
- Rezultat asteptat: Modificarile se salveaza si auditul se logheaza.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0005 - Reset password (P0)

- Preconditii: User activ.
- Pasi:
  - Din meniul user, reset password.
  - Verifica email/flag must change password.
- Rezultat asteptat: Resetul functioneaza fara a expune parola.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0006 - Suspend/reactivate account (P0)

- Preconditii: User activ.
- Pasi:
  - Suspenda cu motiv.
  - Incearca login.
  - Reactiveaza.
- Rezultat asteptat: Userul vede pagina de suspendare si apoi isi recapata accesul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0007 - Archive/restore account (P0)

- Preconditii: User test.
- Pasi:
  - Archive account.
  - Verifica tab archived.
  - Restore.
- Rezultat asteptat: Contul se ascunde din liste normale si poate fi restaurat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0008 - Permanent delete account (P0)

- Preconditii: User test fara risc.
- Pasi:
  - Delete permanently.
  - Confirma.
  - Cauta userul.
- Rezultat asteptat: Stergerea este definitiva si nu rupe auditul.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0009 - Impersonation (P0)

- Preconditii: Admin/owner.
- Pasi:
  - Impersonate user.
  - Verifica banner.
  - Stop impersonation.
- Rezultat asteptat: Intri direct ca user, nu la login; stop revine la admin.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0010 - Workspaces list (P0)

- Preconditii: Admin.
- Pasi:
  - Deschide Workspaces.
  - Cauta workspace.
  - Verifica owner, plan, status, limits.
- Rezultat asteptat: Datele sunt corecte si actiunile relevante sunt in meniu.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0011 - Workspace subscription actions (P0)

- Preconditii: Workspace test.
- Pasi:
  - Extend trial, set trial period, activate, expire, grant manual access, suspend/reactivate.
- Rezultat asteptat: Fiecare actiune modifica statusul real si bannerul userului.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0012 - Demo workspace reset (P1)

- Preconditii: Workspace demo.
- Pasi:
  - Mark demo daca e cazul.
  - Reset demo data.
  - Verifica ce ramane.
- Rezultat asteptat: Se sterg date demo, dar membrii/setarile raman conform descrierii.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0013 - Subscriptions list (P0)

- Preconditii: Admin.
- Pasi:
  - Deschide Subscriptions.
  - Filtreaza status/plan.
  - Aplica actiuni.
- Rezultat asteptat: Lista reflecta aceleasi date ca Workspaces.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0014 - Announcements (P0)

- Preconditii: Admin.
- Pasi:
  - Creeaza announcement global/workspace users/admin.
  - Activeaza/dezactiveaza.
  - Verifica frontend.
- Rezultat asteptat: Anunturile apar doar publicului tinta si in intervalul setat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0015 - Public library moderation (P1)

- Preconditii: Admin.
- Pasi:
  - Deschide Moderation reports.
  - View, keep, hide, delete block, dismiss.
- Rezultat asteptat: Actiunile modifica statusul blocului si logheaza decizia.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0016 - Activity center (P1)

- Preconditii: Admin.
- Pasi:
  - Filtreaza activitati.
  - Deschide detalii.
  - Export CSV.
- Rezultat asteptat: Activitatea este lizibila, filtrabila si exportabila.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0017 - Audit logs (P1)

- Preconditii: Owner/admin.
- Pasi:
  - Deschide audit logs.
  - Cauta actiuni critice recente.
- Rezultat asteptat: Actiunile admin apar cu actor, target, metadata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0018 - System health (P1)

- Preconditii: Admin.
- Pasi:
  - Deschide System health.
  - Verifica queue, scheduler, storage, mail daca exista.
- Rezultat asteptat: Statusurile sunt clare si fara false positive majore.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0019 - Permissions matrix (P1)

- Preconditii: Admin.
- Pasi:
  - Deschide Permissions matrix.
  - Compara cu rolurile testate.
- Rezultat asteptat: Matricea reflecta comportamentul real.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### ADM-0020 - Plans & entitlements (P1)

- Preconditii: Owner/admin.
- Pasi:
  - Deschide Plans.
  - Verifica module si limite pentru demo/free/writer/writer_pro.
- Rezultat asteptat: Planurile sunt clare si coerente cu limitarile reale.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 16. Email, exporturi si fisiere

Fluxuri care depind de SMTP, storage si downloaduri.

### MAIL-0001 - SMTP reset password (P0)

- Preconditii: Email contact@mobilitycloud.eu configurat.
- Pasi:
  - Cere reset password.
  - Verifica expeditor, reply-to, link.
- Rezultat asteptat: Emailul ajunge si linkul este valid.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MAIL-0002 - SMTP workspace invitation (P0)

- Preconditii: Invitatie workspace.
- Pasi:
  - Trimite invitatie.
  - Verifica email.
- Rezultat asteptat: Emailul are subiect/text corect si link functional.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MAIL-0003 - SMTP project-only invitation (P0)

- Preconditii: Invitatie project-only.
- Pasi:
  - Trimite din Project access.
  - Verifica email.
- Rezultat asteptat: Emailul spune clar ca accesul este doar pe proiect.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### MAIL-0004 - Deliverability basics (P1)

- Preconditii: Ai acces la inbox.
- Pasi:
  - Verifica spam, SPF/DKIM/DMARC daca emailul permite.
  - Trimite catre Gmail/Outlook.
- Rezultat asteptat: Emailurile ajung in inbox sau problema este documentata.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### FILE-0001 - Storage private (P0)

- Preconditii: Fisier uploadat.
- Pasi:
  - Copiaza URL direct daca exista.
  - Incearca acces nelogat.
- Rezultat asteptat: Fisierele private nu sunt accesibile public.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### FILE-0002 - Download permissions (P0)

- Preconditii: Fisier intr-un proiect restricted.
- Pasi:
  - Incearca download ca user fara acces.
- Rezultat asteptat: Downloadul este blocat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### FILE-0003 - Nume fisiere export (P1)

- Preconditii: Genereaza PDF/CSV/ZIP.
- Pasi:
  - Verifica denumirea fisierelor.
- Rezultat asteptat: Numele contin proiect/tip/data si nu caractere problematice.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### FILE-0004 - PDF render (P1)

- Preconditii: Documente generate.
- Pasi:
  - Deschide fiecare PDF in browser si Preview/Acrobat.
- Rezultat asteptat: Nu exista pagini goale, text taiat, diacritice lipsa sau tabele rupte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### FILE-0005 - CSV encoding (P1)

- Preconditii: CSV cu diacritice.
- Pasi:
  - Exporta si deschide in Numbers/Excel/Google Sheets.
- Rezultat asteptat: Diacriticele si delimitatorii sunt corecte.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

## 17. Securitate, stabilitate si performanta

Teste transversale inainte de a invita testeri externi.

### SEC-0001 - CSRF/419 normal (P0)

- Preconditii: Sesiune autentificata.
- Pasi:
  - Lasa pagina deschisa peste durata sesiunii.
  - Incearca salvare.
- Rezultat asteptat: Apare mesaj controlat sau refresh/login, nu pagina bruta confuza.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0002 - IDOR proiect (P0)

- Preconditii: Doua workspace-uri/proiecte.
- Pasi:
  - Schimba manual ID in URL.
  - Incearca acces la proiect/document/participant.
- Rezultat asteptat: Accesul strain este 403/404 controlat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0003 - IDOR fisiere (P0)

- Preconditii: Document/fisier in alt proiect.
- Pasi:
  - Incearca URL download cu ID schimbat.
- Rezultat asteptat: Fisierul nu se descarca fara drepturi.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0004 - Upload file validation (P1)

- Preconditii: Fisier executabil/mare/tip invalid.
- Pasi:
  - Incearca upload .php/.exe, fisier foarte mare, imagine, PDF.
- Rezultat asteptat: Tipurile invalide sunt respinse; mesajul este clar.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0005 - XSS text fields (P1)

- Preconditii: Camp text liber.
- Pasi:
  - Introdu <script>alert(1)</script> in descrieri/notes.
  - Vizualizeaza in liste si PDF.
- Rezultat asteptat: Scriptul nu ruleaza; textul este scapat sau sanitizat.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0006 - Rate/error robustness (P1)

- Preconditii: Actiuni rapide repetate.
- Pasi:
  - Apasa de mai multe ori Save/Generate/Upload.
  - Verifica duplicate.
- Rezultat asteptat: Nu se creeaza duplicate necontrolate si butonul are loading/disabled.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0007 - Performance dashboard (P1)

- Preconditii: Workspace cu 50 proiecte/500 participanti.
- Pasi:
  - Deschide dashboard/projects/documents.
  - Noteaza timpi.
- Rezultat asteptat: Pagini cheie incarca acceptabil si fara timeout.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:

### SEC-0008 - Logs dupa test (P1)

- Preconditii: Acces server/admin logs.
- Pasi:
  - Dupa testele P0, verifica laravel.log pentru erori noi.
- Rezultat asteptat: Nu exista 500/SQL errors/permission errors neasteptate.
- Status: [ ] PASS [ ] FAIL [ ] BLOCKED
- Observatii:
