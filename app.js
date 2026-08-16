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

        img.src = "";

        try {
               const response = await fetch("https://ia-image.site.je/api.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ prompt: prompt, model: "openjourney" })
             });   

           

            const data = await response.json();

            if (data.error) {
                alert("Erreur API : " + data.error);
                console.error(data);
                return;
            }

            if (!data.image_base64) {
                alert("Aucune image reçue.");
                console.log(data);
                return;
            }

            img.src = "data:image/png;base64," + data.image_base64;

        } catch (err) {
            alert("Erreur de connexion à l’API.");
            console.error(err);
        }
    };
});
