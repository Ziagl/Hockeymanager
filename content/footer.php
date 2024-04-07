<?php
?>
        </div>
        <script>
            function openTab(evt, tabName) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";
                }
                // Füge diese Zeile hinzu, um den ersten Tab beim Laden zu öffnen
                document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("Games").style.display = "block";
                document.getElementsByClassName("tablinks")[0].className += " active";
            });
        </script>
    </body>
</html>