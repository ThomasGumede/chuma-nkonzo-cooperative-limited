/* MagnificPopup image view */
    $(".popup-image").magnificPopup({
        type: "image",
        gallery: {
            enabled: true,
        },
    });

    /* Nice Select Js */
    $("select").niceSelect();

    

    
    // date picker
    flatpickr("#rs-date", {
    dateFormat: "F j, Y"
    });


    // time picker
    flatpickr("#rs-time", {
    enableTime: true,
    noCalendar: true, 
    dateFormat: "h:i K",
    time_24hr: false
    });