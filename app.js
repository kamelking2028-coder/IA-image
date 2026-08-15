document.addEventListener("DOMContentLoaded", () => {

    const btn = document.getElementById("generate");
    const input = document.getElementById("prompt");
    const img = document.getElementById("result");

    btn.onclick = async () => {
        const prompt = input.value.trim();

        if (!prompt) {
            alert("Écris un prompt avant de générer.");
            return;
        }

        img.src = ""; // reset

        try {
            // Envoi en FormData (compatible avec ton PHP)
            const formData = new FormData();
            formData.append("prompt", prompt);
            formData.append("model", "openjourney"); // tu peux changer ici

            const response = await fetch("https://ia-image.site.je/api.php", {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            // Gestion des erreurs API
            if (data.error) {
                console.error("API error:", data);
                alert("Erreur API : " + data.error);
                return;
            }

            if (!data.image_base64) {
                alert("Aucune image reçue.");
                console.log(data);
                return;
            }

            // Affichage de l'image
            img.src = "data:image/png;base64," + data.image_base64;

        } catch (err) {
            console.error("Erreur JS:", err);
            alert("Erreur de connexion à l’API.");
        }
    };

});
