    
    // Anti FOUC
    // @see http://www.robertmullaney.com/2011/08/29/prevent-flash-of-unstyled-content-fouc-jquery/
    $(function() {
        $('body').hide();
        $(window).load(function(){
            $('body').show();
        });
    });
    
    // Installation de l'application
    manifestUrl = 'http://issy.irwigo.net/manifest.webapp';
    var request = window.navigator.mozApps.install(manifestUrl);
    request.onsuccess = function () {
      // Enregistre l'objet App qui est renvoyé
      var appRecord = this.result;
      alert('Installation réussie !');
    };
    request.onerror = function () {
      // Affiche le nom de l'erreur depuis l'objet DOMError
      alert('Installation échouée, erreur : ' + this.error.name);
    };
    
    // create a map in the "map" div ("Leaflet")
    var map = L.map('map');
    
    // Au chargement de la page "page_poi_list"
    $(document).delegate("#page_poi_list", "pageshow", function()
    {
        setError("");
        
        // Chargement de la liste des catégories
        if($('#categories option').size() == 0) {
            showLoading('Chargement des catégories');
            getCategories([2, 3, 4]);
        }
        
        // Sélection des POI correspondants à la catégoie
        $('#categories').change(function() {
            showLoading();
            cleanPoiList();
            getPois([$( "#categories option:selected" ).val()]); // Appel Ajax au service getPois
        }); // end $('#categories').change()
        
        // Clic sur un POI : stockage de son id
        $('#poi_list').on("click", ".poi_items", function(e) {
            $('#map').hide(); // carte cachée
            showLoading();
            localStorage.setItem('poi_id', $(this).attr('id').replace('poi_', ''));
        });
        
    }); // end $(document).delegate("#page_poi_list")
    
    // Au chargement de la page "page_poi_detail"
    $(document).delegate("#page_poi_detail", "pageshow", function()
    {
        setError("");
        cleanPoiDetail();
        
        poi_id = localStorage.getItem('poi_id');
        getPoiDetail(poi_id); // Appel Ajax au service getPoiDetail avec l'id du POI sélectionné
        
        // Clic sur "Afficher la carte"
        $('#poi_detail').on("click", "#show_map_btn", function() {
            $('#map').show();
            latitude    = $(this).attr('data-latitude');
            longitude   = $(this).attr('data-longitude');
            showMap(latitude, longitude);
        });
    }); // end $(document).delegate("#page_poi_detail")
    
    // Au chargement de la page "page_agenda"
    $(document).delegate("#page_agenda", "pageshow", function()
    {
        setError("");
        showLoading();
        getAgendaList(); // Appel Ajax au service getAgendaList
    });
    
    /*************/
    /* FONCTIONS */
    /*************/
    
    /**
     *  Retourne les catégories
     *  ids  array   Tableau des id des catégories souhaitées
     */
    function getCategories(ids)
    {
        setError("");
        
        url = "service.php";
        data = {
              service   : "getCategories"
            , values    : ids
        };
        
        // Appel Ajax
        $.ajax({
              url: url
            , type: "POST"
            , data: data
            , dataType: "json"
            // , cache: false
            , success: function(response) {
                // console.log(response.value); return false;
                hideLoading();
                
                if ( response.status == 'error' ) {
                    $('span.error_message').html(response.message);
                }
                else if ( response.status == 'success' ) {
                    $.each(response.value, function(row, cat) {
                        $('#categories').append( new Option(cat.nom, cat.id) );
                    });
                    // $("#categories").prepend(new Option('Choisissez une catégorie', '', true, true));
                }
            }
            , error: function() {
                hideLoading();
                setError("Erreur de service");
            }
        });
    } // end getCategories()
    
    /**
     *  Retourne les points d'intérêt d'une catégorie
     *  categories  array   Tableau des id des catégories
     */
    function getPois(categories)
    {
        
        url = "service.php";
        data = {
              service   : "getPois"
            , values    : categories
        };

        // Appel Ajax
        $.ajax({
              url: url
            , type: "POST"
            , data: data
            , dataType: "json"
            , cache: false
            , success: function(response) {
                // console.log(response); return false;
                hideLoading();
                
                if ( response.status == 'error' ) {
                    $('span.error_message').html(response.message);
                }
                else if ( response.status == 'success' ) {
                    pois = [];
                    poi_nb = response.value.length;
                    if ( poi_nb >= 1 ) {
                        $('#poi_list').append('<ul id="pois_listview" class="ui-listview" data-role="listview"></ul>');
                        $.each(response.value, function(i, item) {
                            if ( i == poi_nb - 1 )  css_style = ' ui-last-child'; // dernier élément
                            else if ( i == 0 )      css_style = ' ui-first-child'; // 1er élément
                            else                    css_style = '';
                            id          = item.id;
                            titre       = item.titre;
                            description = item.description;
                            pois.push('<li class="poi_items'+ css_style +'" id="poi_'+ id +'">');
                            pois.push('<a href="#page_poi_detail" class="ui-btn ui-btn-icon-right ui-icon-carat-r ui-link-inherit" data-transition="slide" data-prefetch="true">');
                            pois.push('<h2>'+ titre +'</h2>');
                            pois.push('<p>'+ description +'</p>');
                            pois.push('</a></li>');
                        }); // close each()
                        $('#pois_listview').append( pois.join('') );
                    }
                }
            }
            , error: function() {
                hideLoading();
                setError("Erreur de service");
            }
        });
    } // end getPois()
    
    
    /**
     *  Retourne les détails d'un POI
     *  id  int   Id du POI
     */
    function getPoiDetail(id)
    {
        url = "service.php";
        data = {
              service   : "getPoiDetail"
            , values    : [id]
        };

        // Appel Ajax
        $.ajax({
              url: url
            , type: "POST"
            , data: data
            , dataType: "json"
            , cache: false
            , success: function(response) {
                // console.log(response); return false;
                hideLoading();
                
                if ( response.status == 'error' ) {
                    $('span.error_message').html(response.message);
                }
                else if ( response.status == 'success' ) {
                    content  = '';
                    poi = response.value;
                    content += '<div class="poi_item ui-btn ui-icon-carat-r ui-link-inherit" id="poi_'+ poi.id +'">';
                    content += '<h1>'+ poi.titre + '</h1>';
                    content += '<p class="description">'+ poi.description +'</p>';
                    if ( poi.adresse.length >= 1 ) {
                        content += '<p class="adresse">'+ poi.adresse;
                        // if ( poi.ville.length >= 1 )    content += ' à '+ poi.ville;
                        content += '</p>';
                    }
                    if ( poi.latitude.length >= 1 && poi.longitude.length >= 1 ) {
                        content += '<a data-role="button" id="show_map_btn" data-inline="true" data-latitude="'+ poi.latitude +'" data-longitude='+ poi.longitude +'>';
                        content += '<img alt="Afficher la carte" title="Afficher la carte" src="media/images/map.png" />';
                        content += '</a>';
                    }
                    if ( poi.url.length >= 1 ) {
                        content += '&nbsp;&nbsp;&nbsp;<a href="'+ poi.url +'" target="_blank">';
                        content += '<img alt="Site web" title="Site web" src="media/images/website.png" />';
                        content += '</a>';
                    }
                    if ( poi.email.length >= 1 ) {
                        content += '&nbsp;&nbsp;&nbsp;<a href="mailto:'+ poi.email +'">';
                        content += '<img alt="Contact e-mail" title="Contact e-mail" src="media/images/email.png" />';
                        content += '</a>';
                    }
                    if ( poi.telephone.length >= 1 ) {
                        content += '&nbsp;&nbsp;&nbsp;<a href="tel:'+ poi.url +'">';
                        content += '<img alt="Téléphone" title="Téléphone" src="media/images/mobile.png" title="'+ poi.telephone +'" />';
                        content += '</a>';
                    }
                    content += '</div>';
                    $('#poi_detail').html(content);
                }
            }
            , error: function() {
                hideLoading();
                setError("Erreur de service");
            }
        });
    } // end getPoiDetail()
    
    /**
     *  Retourne les événements de l'agenda
     *  nb_evts     int     Nombre d'événements souhaités (20 par défaut)
     */
    function getAgendaList(nb_evts)
    {
        setError("");
        
        // Vérif paramètre
        if ( $.type(nb_evts) === "undefined" ) {
            nb_evts = 20;
        }
        // Vérifie si nb_evts est un entier
        if ( $.type(nb_evts) !== "number" || nb_evts <= 0 ) {
            setError("Erreur de paramètre");
            hideLoading();
            return false;
        }
        
        url = "service.php";
        data = {
              service   : "getAgendaList"
            , values    : [nb_evts]
        };
        
        // Appel Ajax
        $.ajax({
              url: url
            , type: "POST"
            , data: data
            , dataType: "json"
            , cache: false
            , success: function(response) {
                // console.log(response.value); return false;
                hideLoading();
                
                if ( response.status == 'error' ) {
                    $('span.error_message').html(response.message);
                }
                else if ( response.status == 'success' ) {
                    evts = [];
                    evts_nb = response.value.length;
                    if ( evts_nb >= 1 ) {
                        $('#agenda_list').append('<ul id="agenda_listview" class="ui-listview" data-role="listview"></ul>');
                        $.each(response.value, function(i, item) {
                            if ( i == evts_nb - 1 ) css_style = ' ui-last-child'; // dernier élément
                            else if ( i == 0 )      css_style = ' ui-first-child'; // 1er élément
                            else                    css_style = '';
                            id          = item.nid;
                            titre       = item.titre;
                            add_url     = item.add_url;
                            center      = item.center;
                            date        = item.date;
                            date_iso    = item.date_iso;
                            description = item.description;
                            lieu        = item.lieu;
                            time        = item.time;
                            url         = item.url;
                            evts.push('<li class="evt_items'+ css_style +'" id="evt_'+ id +'">');
                            //  href="#evt-detail"
                            evts.push('<div class="align-left ui-btn ui-link-inherit" data-transition="slide" data-prefetch="true">');
                            evts.push('<h2>'+ titre +'</h2>');
                            if ( description != null ) {
                                evts.push('<p class="description">'+ description +'</p>');
                            }
                            if ( format_date_iso(date_iso) ) {
                                evts.push('<p>'+ format_date_iso(date_iso) +'</p>');
                            }
                            if ( lieu != null ) {
                                evts.push('<p>'+ lieu.charAt(0).toUpperCase() + lieu.slice(1) +'</p>');
                            }
                            if ( url.length >= 1 && url != null ) {
                                evts.push('<p><a href="'+url+'" target="_blank">');
                                evts.push('<img alt="Site web" title="Site web" src="media/images/website.png" />');
                                evts.push('</a></p>');
                            }
                            evts.push('</div></li>');
                        }); // close each()
                        $('#agenda_listview').append( evts.join('') );
                    }
                }
            }
            , error: function() {
                hideLoading();
                setError("Erreur de service");
            }
        });
    } // end getAgendaList()
    
    
    /**
     *  Affiche une carte centrée sur la latitude/longitude (librairie Leaflet)
     */
    function showMap(latitude, longitude)
    {
        // set the view to a given place and zoom
        map.setView([latitude, longitude], 15);

        // add an OpenStreetMap tile layer
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // add a marker in the given location, attach some popup content to it and open the popup
        L.marker([latitude, longitude]).addTo(map)
            // .bindPopup('A pretty CSS3 popup. <br> Easily customizable.')
            .openPopup();
    }
    
    // Définit le texte d'erreur
    function setError(msg) {
        $('span.error_message').html(msg);
    }
    
    // Vide la liste des POI
    function cleanPoiList() {
        $('#poi_list').html("");
    }
    
    // Vide le détail d'un POI
    function cleanPoiDetail() {
        $('#poi_detail').html("");
    }
    
    // Vide la liste de l'agenda
    function cleanAgenda() {
        $('#agenda_list').html("");
    }
    
    // Formate une date_iso (2014-03-15T20:30:00) en date/heure fr (15-03-2014 à 20h30)
    function format_date_iso(date_iso)
    {    
        // Vérification du format d'entrée
        if ( date_iso.length != 19 || date_iso == null ) {
            console.log('date_iso KO');
            return false;
        }
        
        // Vérification de la séparation date/heure
        date_array = date_iso.split('T');
        if (date_array[0].length != 10 || date_array[1].length != 8) {
            console.log('date_array KO');
            return false;
        }
        
        // Vérification format date AAAA-MM-JJ
        date_amj = date_array[0].split('-');
        if ( date_amj[2].length != 2 || date_amj[1].length != 2 || date_amj[0].length != 4 ) {
            console.log('date_amj KO');
            return false;
        }
        
        // Vérification format heure
        date_time = date_array[1].split(':');
        if ( date_time[0].length != 2 || date_time[1].length != 2 ) {
            return false;
        }
        
        return 'Le '+ date_amj[2] +' '+ format_mois_num_to_letter(date_amj[1], false) +' '+ date_amj[0] + ' à ' + date_time[0] + 'h' + date_time[1];
    } // end format_date_iso()
    
    // Formate le mois : numéro => lettre (3 => mars)
    // param 'upper' : si true (par défaut), renvoie l'initiale du mois en majuscule
    function format_mois_num_to_letter(num, upper) {
        if ( num.length == 1 ) num = '0' + num; // 3 => 03
        if ( num < '01' || num > '12' ) { return num; }
        upper = (typeof upper === "undefined") ? true : upper; // valeur true par défaut
        mois = '';
        switch ( num ) {
            case '01' :
                if (upper)  mois = 'Janvier';
                else        mois = 'janvier';
                break;
            case '02' :
                if (upper)  mois = 'Février';
                else        mois = 'février';
                break;
            case '03' :
                if (upper)  mois = 'Mars';
                else        mois = 'mars';
                break;
            case '04' :
                if (upper)  mois = 'Avril';
                else        mois = 'avril';
                break;
            case '05' :
                if (upper)  mois = 'Mai';
                else        mois = 'mai';
                break;
            case '06' :
                if (upper)  mois = 'Juin';
                else        mois = 'juin';
                break;
            case '07' :
                if (upper)  mois = 'Juillet';
                else        mois = 'juillet';
                break;
            case '08' :
                if (upper)  mois = 'Août';
                else        mois = 'août';
                break;
            case '09' :
                if (upper)  mois = 'Septembre';
                else        mois = 'septembre';
                break;
            case '10' :
                if (upper)  mois = 'Octobre';
                else        mois = 'octobre';
                break;
            case '11' :
                if (upper)  mois = 'Novembre';
                else        mois = 'novembre';
                break;
            case '12' :
                if (upper)  mois = 'Décembre';
                else        mois = 'décembre';
                break;
            default : 
                mois = num;
                break;
        }
        return mois;
    } // end format_mois_num_to_letter()