function initCarCalendar(min, max) {
  var y = new Date().getFullYear();
  new Calendar("car-calendar", {
    minYear: Math.min(min, y),
    maxYear: Math.max(max, y),
    url: WEB_URL + "index.php/car/model/calendar/toJSON",
    onclick: function() {
      send(
        WEB_URL + "index.php/car/model/index/action",
        "action=detail&id=" + this.id,
        doFormSubmit
      );
    }
  });
  forEach($E('car_links').getElementsByTagName('a'), function() {
    callClick(this, function() {
      send(
        WEB_URL + "index.php/car/model/vehicles/action",
        'action=detail&id=' + this.id.replace('car_', ''),
        doFormSubmit,
        this
      );
    });
  });
}