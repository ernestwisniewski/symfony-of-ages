import { Controller } from "@hotwired/stimulus";

import.meta.stimulusFetch = "eager";
import.meta.stimulusIdentifier = "welcome";

export default class controller extends Controller {

  static targets = ["title"];
  static values = {
    name: String,
  };
  connect() {
    this.titleTarget.textContent = `hello ${this.nameValue}`;
  }
}
