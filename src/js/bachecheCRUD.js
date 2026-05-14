// ---------------------------------------------------------
// MODIFICA NOME BACHECA
// ---------------------------------------------------------
function modificaBacheca(nomeAttuale, owner) {

    const nuovoNome = prompt(
        `Nuovo nome per la bacheca:\n"${nomeAttuale}"`,
        nomeAttuale
    );

    if (nuovoNome === null) return; // annullato

    const nuovoNomeTrim = nuovoNome.trim();

    if (nuovoNomeTrim === '') {
        alert('Il nome non può essere vuoto.');
        return;
    }

    if (nuovoNomeTrim === nomeAttuale) {
        alert('Il nome è uguale a quello attuale.');
        return;
    }

    // Conferma
    const conferma = confirm(
        `Rinominare la bacheca da:\n"${nomeAttuale}"\na:\n"${nuovoNomeTrim}"?`
    );

    if (!conferma) return;

    // Richiesta al server
    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione:      'modifica',
            nome:        nomeAttuale,
            owner:       owner,
            nuovoNome:   nuovoNomeTrim
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            alert('Bacheca rinominata con successo.');
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// ELIMINA BACHECA
// ---------------------------------------------------------
function eliminaBacheca(nome, owner) {

    const conferma = confirm(
        `Eliminare definitivamente la bacheca:\n"${nome}"?\n\nL'operazione non è reversibile.`
    );

    if (!conferma) return;

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'elimina',
            nome:   nome,
            owner:  owner
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            alert('Bacheca eliminata con successo.');
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// AGGIUNGI NUOVA BACHECA
// ---------------------------------------------------------
function aggiungiBacheca() {
    const nome = prompt("Inserisci il nome della nuova bacheca:");
    if (nome === null || nome.trim() === "") return;

    const owner = prompt("Inserisci il Codice del proprietario (ogni bacheca è associata ad un codice utente segreto, l'identificativo dell'utente):");
    if (owner === null || owner.trim() === "") return;

    if (isNaN(owner.trim())) {
        alert("Errore: Il codice del proprietario deve essere un numero valido.");
        return;
    }

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'aggiungi',
            nome: nome.trim(),
            owner: owner.trim()
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            alert('Bacheca creata con successo.');
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// RIMUOVI UTENTE AUTORIZZATO
// ---------------------------------------------------------
function rimuoviAutorizzato(nomeBacheca, owner, utenteDaRimuovere) {
    if (!confirm("Rimuovere l'utente da questa bacheca?")) return;

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'rimuovi_autorizzato',
            nome: nomeBacheca,
            owner: owner,
            utenteDaRimuovere: utenteDaRimuovere
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// AGGIUNGI UTENTE AUTORIZZATO
// ---------------------------------------------------------
function aggiungiAutorizzato(nomeBacheca, owner) {
    const codiceUtente = prompt("Inserisci il Codice dell'utente da autorizzare per questa bacheca:");
    
    if (codiceUtente === null || codiceUtente.trim() === "") return;

    if (isNaN(codiceUtente.trim())) {
        alert("Errore: Il codice utente deve essere un numero valido.");
        return;
    }

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'aggiungi_autorizzato',
            nome: nomeBacheca,
            owner: owner,
            nuovoUtente: codiceUtente.trim()
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            alert('Utente aggiunto con successo alla bacheca.');
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// AGGIUNGI FILE
// ---------------------------------------------------------
function aggiungiFile(nomeBacheca, owner) {
    const idFile = prompt("Inserisci l'ID (numero) del file da pubblicare in questa bacheca:");
    
    if (idFile === null || idFile.trim() === "") return;

    if (isNaN(idFile.trim())) {
        alert("Errore: L'ID del file deve essere un numero valido.");
        return;
    }

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'aggiungi_file',
            nome: nomeBacheca,
            owner: owner,
            nuovoFile: idFile.trim()
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            alert('File aggiunto con successo alla bacheca.');
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}

// ---------------------------------------------------------
// RIMUOVI FILE
// ---------------------------------------------------------
function rimuoviFile(nomeBacheca, owner, fileDaRimuovere) {
    if (!confirm("Rimuovere questo file dalla bacheca?")) return;

    fetch('bacheche.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            azione: 'rimuovi_file',
            nome: nomeBacheca,
            owner: owner,
            fileDaRimuovere: fileDaRimuovere
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.successo) {
            location.reload();
        } else {
            alert('Errore: ' + data.messaggio);
        }
    })
    .catch(() => alert('Errore di comunicazione con il server.'));
}