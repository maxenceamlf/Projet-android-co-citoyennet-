package com.example.powerhome;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.gson.JsonObject;
import com.koushikdutta.ion.Ion;

/**
 * LoginActivity — Connexion du résident.
 *
 * Envoie les identifiants au serveur PHP via un POST HTTP (bibliothèque Ion).
 * En cas de succès, sauvegarde le token dans SessionManager et
 * redirige vers MainActivity.
 *
 * Bibliothèque réseau utilisée : Ion (com.koushikdutta.ion)
 * Ion gère automatiquement les threads (requête en background, callback sur UI thread).
 */
public class LoginActivity extends AppCompatActivity {

    private EditText    etEmail, etPassword;
    private Button      btnLogin, btnRegister, btnForgotPassword;
    private ProgressBar progressBar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // Liaison des vues
        etEmail          = findViewById(R.id.et_email);
        etPassword       = findViewById(R.id.et_mdp);
        btnLogin         = findViewById(R.id.btn_login);
        btnRegister      = findViewById(R.id.btn_register);
        btnForgotPassword = findViewById(R.id.btn_forgot_password);
        progressBar      = findViewById(R.id.progress_bar);

        // Listeners
        btnLogin.setOnClickListener(v -> attemptLogin());

        btnRegister.setOnClickListener(v ->
                startActivity(new Intent(this, RegisterActivity.class)));

        btnForgotPassword.setOnClickListener(v ->
                startActivity(new Intent(this, ForgotPasswordActivity.class)));
    }

    /**
     * Valide les champs localement, puis envoie la requête au serveur.
     */
    private void attemptLogin() {
        String email    = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        // Validation côté client (rapide, avant d'appeler le réseau)
        if (email.isEmpty()) {
            etEmail.setError("Champ obligatoire");
            return;
        }
        if (password.isEmpty()) {
            etPassword.setError("Champ obligatoire");
            return;
        }

        setLoading(true);

        // --- Requête POST avec Ion ---
        Ion.with(this)
                .load("POST", Constants.URL_LOGIN)
                .setBodyParameter("email",    email)
                .setBodyParameter("password", password)
                .asJsonObject()
                .setCallback((e, result) -> {
                    // Ce callback s'exécute sur le thread UI (safe pour modifier les vues)
                    setLoading(false);

                    if (e != null || result == null) {
                        // Erreur réseau (pas de connexion, timeout, etc.)
                        Toast.makeText(this, "Impossible de joindre le serveur. Vérifiez votre connexion.", Toast.LENGTH_LONG).show();
                        return;
                    }

                    handleLoginResponse(result);
                });
    }

    /**
     * Traite la réponse JSON du serveur.
     * Format attendu : { "success": true, "data": { "token": "...", "user": {...} } }
     */
    private void handleLoginResponse(JsonObject response) {
        boolean success = response.get("success").getAsBoolean();

        if (!success) {
            String message = response.get("message").getAsString();
            Toast.makeText(this, message, Toast.LENGTH_SHORT).show();
            return;
        }

        // Extraire les données
        JsonObject data = response.getAsJsonObject("data");
        String     token = data.get("token").getAsString();
        JsonObject user  = data.getAsJsonObject("user");

        // Valeurs optionnelles avec fallback
        String phone = user.has("phone") && !user.get("phone").isJsonNull()
                ? user.get("phone").getAsString() : "";
        int habitatId = user.has("habitat_id") && !user.get("habitat_id").isJsonNull()
                ? user.get("habitat_id").getAsInt() : -1;
        int ecoCoins = user.has("eco_coins") ? user.get("eco_coins").getAsInt() : 0;

        // Sauvegarder la session localement
        SessionManager session = new SessionManager(this);
        session.saveSession(
                token,
                user.get("id").getAsInt(),
                user.get("firstname").getAsString(),
                user.get("lastname").getAsString(),
                user.get("email").getAsString(),
                phone,
                habitatId,
                ecoCoins
        );

        // Aller à l'écran principal
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }

    /**
     * Active ou désactive l'indicateur de chargement.
     * On désactive aussi les boutons pour éviter les doubles clics.
     */
    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        btnLogin.setEnabled(!loading);
        btnRegister.setEnabled(!loading);
    }
}
