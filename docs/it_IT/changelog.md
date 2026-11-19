---
layout: default
lang: it_IT
---

# Changelog di Groupir

# 19/11/2026 - 1.0.0

- Prima versione pubblicata sul Market di Jeedom
- Comandi info: tipo Binario, Numerico o Altro (on / off predefinito), proposto in base ai comandi info scelti
- Numerico: media, più alto, più basso, somma, mediana, numero di corrispondenze o secondo la regola; decimali e unità ripresa automaticamente
- Opzione «Verifica l'aggiornamento dei dati»: dispositivi senza aggiornamenti recenti o in timeout ignorati, con avviso nel widget e nel log
- Comando info: lo stesso dispositivo può comparire su più righe (non lo stesso comando info)
- Comandi attivabili / disattivabili
- Editor: valore attuale nell'intestazione dei comandi, nome del gruppo ricordato, scenari in un blocco separato (info), righe scenario clonabili
- Scheda Visualizzazione nascosta nella configurazione dei comandi (senza effetto sul widget del gruppo)
- Rimossa la sezione «Sviluppo» dalla configurazione del plugin
- Pulizia del codice
- Interfaccia tradotta in inglese, tedesco, spagnolo e italiano
- Documentazione: sezione «Configurazione del plugin»

# 19/11/2026 - 0.5

**Comandi del gruppo**
- Comando info: regola a scelta, «tutti», «almeno uno» o «almeno N» dispositivi che corrispondono
- Comando info: può contare lo stato di scenari (attivo, inattivo, in corso, fermo), ricalcolato ogni minuto
- Comando azione: più azioni concatenate sullo stesso dispositivo (es. On, Luminosità, Colore)
- Comando azione: avviare, fermare, attivare o disattivare scenari
- Comando azione: pausa facoltativa tra due azioni (da 0 a 30 secondi)
- Valore di un cursore: intervallo del comando come suggerimento (es. 153 a 500) e inserimento limitato a tale intervallo

**Scheda Correspondances**
- Comandi, righe e azioni riordinabili con trascina e rilascia
- Comandi comprimibili, compressi per impostazione predefinita
- Pulsanti per clonare una riga (comandi ritrovati per nome sul nuovo dispositivo, valori mantenuti) o un intero comando
- Icona di configurazione su ogni comando: cronologia, tipo generico, avvisi, visualizzazione
- Le righe il cui dispositivo, comando o scenario è stato eliminato sono mostrate in rosso
- Salvataggio rifiutato con un messaggio preciso (comando e riga) se una riga è incompleta

**Widget e pagina**
- Widget dedicato: stato di ogni comando info (pallino, numero di dispositivi conformi), colore attuale delle luci, un pulsante per comando azione
- Scheda Generale: blocco «Scenari collegati» (scenari che usano il gruppo e scenari controllati dal gruppo)
- Interfaccia rinnovata nello stile di Scan.Ip e nuovo logo

**Prestazioni**
- Vengono ricalcolati solo i comandi interessati da una modifica
- Stato del gruppo in cache, widget aggiornato in tempo reale senza essere rigenerato

**Correzioni**
- I comandi del gruppo potevano essere cancellati al salvataggio (versione di sviluppo)

# 18/11/2026 - 0.4

- Il plugin diventa **Groupir** (id `groupir`) e si limita al raggruppamento di dispositivi (in precedenza Simple.Scenario)
- Gruppi composti da comandi info (valore «ok / ko», personalizzabile) e comandi azione, ognuno dei quali è un sottogruppo di dispositivi
- Selezione in linea Oggetto > Dispositivo > Comando con filtri; un dispositivo già scelto in un comando non viene più proposto
- Sviluppo: pulsante «Scarica il branch» nella configurazione del plugin
