# Follow
Ermöglicht es Usern, anderen Charakteren zu folgen & somit via MyAlerts über ihre Inplay-Aktivität geupdatet zu werden. Dabei werden Alerts für neue Inplayszenen und -posts sowie neu eingereichte Inplayzitate erstellt. <b>Achtung</b>, nach der Installation müssen in den Plugin-Dateien von Inplayzitaten und Inplaytracker noch Änderungen vorgenommen werden, die weiter unten aufgeführt werden.

<b>Hinweise</b>
Der Alert wird immer an den Account geschickt, mit den initial gefolgt wurde. Daher ist es hilfreich, entweder <a href="https://github.com/katjalennartz/characterAlert">Character Alert</a> von risuena zu installieren oder <a href="https://storming-gates.de/showthread.php?tid=1000510">dieses Tutorial</a> (MyAlerts-Integration mit Accountswitcher) eingebaut zu haben.

# Datenbankänderungen

Neue Tabellen:
- follow

# Templateänderungen

Neue Templates:
- member_profile_follow
- member_profile_unfollow

In das Template member_profile wird die Variable {$follow_button} eingebaut - falls diese nicht automatisch eingesetzt werden konnte, muss sie von euch händisch ergänzt werden.

# Inplayquotes-Plugin mit Follow-Funktion verbinden
