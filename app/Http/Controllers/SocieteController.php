<?php

namespace App\Http\Controllers;

use App\Models\Societe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocieteController extends Controller
{
    /**
     * Crée ou met à jour la société de l'utilisateur connecté.
     *
     * Modèle "une société par utilisateur" : on cible toujours la société liée
     * à l'utilisateur authentifié (updateOrCreate sur user_id), ce qui empêche
     * tout accès à la société d'un autre compte (pas d'IDOR). La validation +
     * l'ORM Eloquent garantissent des requêtes préparées (anti-injection SQL).
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'nom_societe' => 'required|string|max:255',
            'if'          => 'nullable|string|max:15',
            'ice'         => 'nullable|string|max:15',
            'rc'          => 'nullable|string|max:50',
            'cnss'        => 'nullable|string|max:50',
        ]);

        $societe = Societe::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return redirect()->back()->with(
            'success',
            $societe->wasRecentlyCreated
                ? "Société « {$societe->nom_societe} » enregistrée. Vous pouvez importer votre balance."
                : "Informations de la société mises à jour."
        );
    }
}
