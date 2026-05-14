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