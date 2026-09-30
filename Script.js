  document.addEventListener('DOMContentLoaded', function() {
        // Configuration de la carte
        // Clé TomTom côté navigateur : par nature publique (elle est lue
        // par le client), elle doit être restreinte à vos domaines depuis
        // la console TomTom. La clé du serveur vit dans config.php.
        const apiKey = 'VOTRE_CLE_API_TOMTOM';
        const map = tt.map({
            key: apiKey,
            container: 'map',
            center: [2.2137, 46.2276],
            zoom: 6
        });
    
        let markers = [];
        let routeLayerAdded = false;
        const calculateBtn = document.getElementById('calculateBtn');
        const confirmBtn = document.getElementById('confirmBtn');
    
        // Fonction pour effacer la carte
        function clearMap() {
            markers.forEach(marker => marker.remove());
            markers = [];
            
            if (routeLayerAdded) {
                map.removeLayer('route');
                map.removeSource('route');
                routeLayerAdded = false;
            }
        }
    
        // Fonction pour géocoder une adresse
        async function geocodeAddress(address) {
            try {
                const response = await tt.services.fuzzySearch({
                    key: apiKey,
                    query: address
                });
                
                if (response.results && response.results.length > 0) {
                    return response.results[0].position;
                } else {
                    throw new Error('Adresse non trouvée');
                }
            } catch (error) {
                console.error('Erreur de géocodage:', error);
                throw error;
            }
        }

        // Fonction pour calculer le prix de base selon la distance
        function calculateBasePrice(distanceKm) {
            // Tarif de base
            const basePrice = 4.40;
            
      // Déterminer le tarif selon la tranche horaire
            let ratePerKm, tarifType;
            if (currentTime >= 7 && currentTime < 19) {
            ratePerKm = 1.50; // Tarif jour
                tarifType = "Tarif jour (07h-19h)";
            } else {
            ratePerKm = 2.00; // Tarif nuit
            tarifType = "Tarif nuit (19h-07h)";
            }
            console.log(`Calcul du prix: ${tarifType} - ${ratePerKm}€/km`);
            // Calcul du prix total

            return basePrice + (distanceKm * ratePerKm);
        }

        // Fonction pour calculer les options supplémentaires
        function calculateOptionsPrice() {
            const fifthPassenger = document.getElementById('fifthPassenger').checked;
            const luggageCount = parseInt(document.getElementById('luggage').value) || 0;
            
            return (fifthPassenger ? 4 : 0) + (luggageCount * 2);
        }
    
        // Fonction pour afficher les détails du prix
        function showPriceDetails(distanceKm, basePrice, optionsPrice) {
            const details = 
                <div class="price-details">
                    <div>Prix de base: ${basePrice.toFixed(2)}€ (${distanceKm} km)</div>
                    <div>Options: +${optionsPrice.toFixed(2)}€</div>
                    <div>5ème passager: ${document.getElementById('fifthPassenger').checked ? 'Oui (+4€)' : 'Non'}</div>
                    <div>Bagages: ${document.getElementById('luggage').value} (+${parseInt(document.getElementById('luggage').value) * 2}€)</div>
                </div>
             ;
            
            // Supprimer les anciens détails s'ils existent
            const existingDetails = document.querySelector('.price-details');
            if (existingDetails) existingDetails.remove();
            
            // Ajouter les nouveaux détails
            document.getElementById('price').insertAdjacentHTML('afterend', details);
        }

        // Fonction principale pour calculer l'itinéraire
        async function calculateRoute() {
            try {
                clearMap();
                
                const departure = document.getElementById('departure').value;
                const arrival = document.getElementById('arrival').value;
                
                if (!departure || !arrival) {
                    alert('Veuillez saisir les adresses de départ et d\'arrivée');
                    return;
                }

                // Afficher l'indicateur de chargement
                calculateBtn.classList.add('loading');
                calculateBtn.disabled = true;

                // Géocodage des adresses
                const startPos = await geocodeAddress(departure);
                const endPos = await geocodeAddress(arrival);

                // Ajouter les marqueurs
                const startMarker = new tt.Marker().setLngLat(startPos).addTo(map);
                const endMarker = new tt.Marker().setLngLat(endPos).addTo(map);
                markers.push(startMarker, endMarker);

                // Calculer l'itinéraire
                const routeResponse = await tt.services.calculateRoute({
                    key: apiKey,
                    locations: [startPos, endPos],
                    travelMode: 'car'
                });

                // Afficher l'itinéraire sur la carte
                const routeGeoJson = routeResponse.toGeoJson();
                
                map.addSource('route', {
                    type: 'geojson',
                    data: routeGeoJson
                });
                
                map.addLayer({
                    id: 'route',
                    type: 'line',
                    source: 'route',
                    paint: {
                        'line-color': '#d4af37',
                        'line-width': 5
                    }
                });
                routeLayerAdded = true;

                // Ajuster la vue de la carte
                map.fitBounds([startPos, endPos], { padding: 100 });

                // Calcul des résultats
                const summary = routeResponse.routes[0].summary;
                const distanceKm = (summary.lengthInMeters / 1000).toFixed(1);
                const durationMin = Math.round(summary.travelTimeInSeconds / 60);
                
                // Calcul des prix
                const basePrice = calculateBasePrice(parseFloat(distanceKm));
                const optionsPrice = calculateOptionsPrice();
                const totalPrice = basePrice + optionsPrice;

                // Afficher les résultats
                document.getElementById('distance').textContent = `${distanceKm} km`;
                document.getElementById('duration').textContent = `${durationMin} min`;
                document.getElementById('price').textContent = `${totalPrice.toFixed(2)} €`;
                
                // Afficher les détails du prix
                showPriceDetails(distanceKm, basePrice, optionsPrice);
                
                // Afficher la section des résultats
                document.getElementById('results').style.display = 'grid';
                confirmBtn.style.display = 'block';

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors du calcul du trajet: ' + error.message);
            } finally {
                calculateBtn.classList.remove('loading');
                calculateBtn.disabled = false;
            }
        }

        // Gestion de la soumission du formulaire
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            calculateRoute();
        });

        // Gestion du bouton de confirmation
        confirmBtn.addEventListener('click', function() {
            const firstName = document.getElementById('firstName').value;
            const departure = document.getElementById('departure').value;
            const arrival = document.getElementById('arrival').value;
            const price = document.getElementById('price').textContent;
            const date = document.getElementById('date').value;
            const time = document.getElementById('time').value;
            
            const confirmationMessage = `
                Merci ${firstName} !
                
                Votre réservation est confirmée pour le ${date} à ${time}:
                - De: ${departure}
                - À: ${arrival}
                - Prix total: ${price}
                
                Un chauffeur premium vous contactera pour confirmation.
            `;
            
            alert(confirmationMessage);

                  if (!/^\+?[0-9]{6,}$/.test(phone, 10)) {
        err.textContent = 'Numéro de téléphone invalide.';
        return;
      }
            
        });
    });