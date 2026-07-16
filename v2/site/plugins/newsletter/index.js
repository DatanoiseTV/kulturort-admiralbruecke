panel.plugin("kulturort/newsletter", {
  components: {
    "k-newsletter-view": {
      props: {
        confirmed: Number,
        pending: Number,
        subscribers: Array,
        history: Array
      },
      data() {
        return {
          subject: "",
          text: "",
          busy: false,
          notice: null,
          noticeTheme: "positive",
          list: this.subscribers,
          log: this.history,
          stats: { confirmed: this.confirmed, pending: this.pending }
        };
      },
      methods: {
        async reload() {
          const data = await this.$api.get("newsletter/data");
          this.list = data.subscribers;
          this.log = data.history;
          this.stats = { confirmed: data.confirmed, pending: data.pending };
        },
        async sendTest() {
          this.busy = true;
          try {
            const result = await this.$api.post("newsletter/test", {
              subject: this.subject, text: this.text
            });
            this.notice = result.message;
            this.noticeTheme = result.ok ? "positive" : "negative";
          } finally {
            this.busy = false;
          }
        },
        async sendAll() {
          if (!window.confirm(
            "Wirklich an alle " + this.stats.confirmed +
            " bestätigten Abonnent:innen senden?")) {
            return;
          }
          this.busy = true;
          try {
            const result = await this.$api.post("newsletter/send", {
              subject: this.subject, text: this.text
            });
            this.notice = result.message;
            this.noticeTheme = result.ok ? "positive" : "negative";
            if (result.ok) {
              this.subject = "";
              this.text = "";
            }
            await this.reload();
          } finally {
            this.busy = false;
          }
        },
        async removeSubscriber(email) {
          if (!window.confirm("Adresse " + email + " wirklich löschen?")) {
            return;
          }
          await this.$api.post("newsletter/delete", { email: email });
          await this.reload();
        }
      },
      template: `
        <k-panel-inside class="k-newsletter-view">
          <k-header>Newsletter</k-header>

          <k-notification v-if="notice" :theme="noticeTheme">{{ notice }}</k-notification>

          <k-stats :reports="[
            { label: 'Bestätigt', value: String(stats.confirmed) },
            { label: 'Unbestätigt', value: String(stats.pending) },
            { label: 'Aussendungen', value: String(log.length) }
          ]" size="large" style="margin-bottom: 2rem" />

          <k-section headline="Newsletter schreiben">
            <k-text-field
              :value="subject"
              @input="subject = $event"
              name="subject" label="Betreff" :required="true" />
            <k-textarea-field
              :value="text"
              @input="text = $event"
              name="text" label="Text (reine Text-Mail; Links zur Seite werden automatisch für die Statistik markiert)"
              :buttons="false" size="huge" :required="true" />
            <k-button-group style="margin-top: 1rem">
              <k-button icon="wand" variant="filled" :disabled="busy || !subject || !text"
                        @click="sendTest">Testmail an kontakt@</k-button>
              <k-button icon="email" theme="notice" variant="filled"
                        :disabled="busy || !subject || !text"
                        @click="sendAll">An alle Bestätigten senden</k-button>
            </k-button-group>
          </k-section>

          <k-section headline="Abonnent:innen">
            <table class="k-newsletter-table">
              <thead>
                <tr><th>E-Mail</th><th>Status</th><th>Sprache</th><th>Seit</th><th></th></tr>
              </thead>
              <tbody>
                <tr v-for="entry in list" :key="entry.email">
                  <td>{{ entry.email }}</td>
                  <td>{{ entry.status === 'confirmed' ? 'bestätigt' : 'unbestätigt' }}</td>
                  <td>{{ entry.language }}</td>
                  <td>{{ (entry.confirmed || entry.signedUp || '').slice(0, 10) }}</td>
                  <td>
                    <k-button icon="trash" size="xs"
                              @click="removeSubscriber(entry.email)">löschen</k-button>
                  </td>
                </tr>
              </tbody>
            </table>
          </k-section>

          <k-section headline="Versandhistorie">
            <table class="k-newsletter-table">
              <thead><tr><th>Datum</th><th>Betreff</th><th>Empfänger</th></tr></thead>
              <tbody>
                <tr v-for="entry in log" :key="entry.ts">
                  <td>{{ entry.ts.slice(0, 16).replace('T', ' ') }}</td>
                  <td>{{ entry.subject }}</td>
                  <td>{{ entry.recipients }}</td>
                </tr>
              </tbody>
            </table>
          </k-section>
        </k-panel-inside>
      `
    }
  }
});
