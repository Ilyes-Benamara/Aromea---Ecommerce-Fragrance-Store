// Aromea chatbot widget. Self-contained: include this script on any page
// and a floating button + chat panel will be injected automatically.

(function () {
  // Guided questions asked before handing off to the AI.
  const QUESTIONS = [
  {
    key: "gender",
    text: "Welcome to Aromea ✦\n\nI'm your personal scent guide. Let's find your perfect fragrance.\n\nWho is this fragrance for?",
    options: [
      { label: "For Him",  value: "him"    },
      { label: "For Her",  value: "her"    },
      { label: "Unisex",   value: "unisex" },
      { label: "A Gift",   value: "any"    },
    ],
  },
  {
    key: "family",
    text: "What do you want it to smell like?",
    options: [
      { label: "Woody and Strong",    value: "woody"    },
      { label: "Sweet and Fruity",    value: "fruity"   },
      { label: "Powdery and Vanilla", value: "powdery"  },
      { label: "Fresh and Clean",     value: "fresh"    },
    ],
  },
  {
    key: "budget",
    text: "What is your budget?",
    options: [
      { label: "Less than 10,000 DZD",      value: "under_10k"  },
      { label: "10,000 – 20,000 DZD",       value: "10k_20k"    },
      { label: "20,000 – 30,000 DZD",       value: "20k_30k"    },
      { label: "More than 30,000 DZD",      value: "above_30k"  },
    ],
  },
  {
    key: "occasion",
    text: "On what occasion will it be worn?",
    options: [
      { label: "Daily Use",            value: "daily"    },
      { label: "Parties & Occasions",  value: "evening"  },
      { label: "Date Nights",          value: "romantic" },
      { label: "Other",                value: "other"    },
    ],
  },
  {
    key: "season",
    text: "Which season will this fragrance be worn in?",
    options: [
      { label: "Winter", value: "winter" },
      { label: "Autumn", value: "fall"   },
      { label: "Spring", value: "spring" },
      { label: "Summer", value: "summer" },
    ],
  },
  {
    key: "age",
    text: "How old is the fragrance's future owner?",
    options: [
      { label: "Under 20",  value: "under_20" },
      { label: "20 – 30",   value: "20_30"    },
      { label: "30 – 40",   value: "30_40"    },
      { label: "Over 40",   value: "above_40" },
    ],
  },
  {
    key: "occupation",
    text: "What's their occupation?",
    options: [
      { label: "Student",       value: "student"       },
      { label: "Doctor",        value: "doctor"        },
      { label: "Teacher",       value: "teacher"       },
      { label: "Office Worker", value: "office_worker" },
      { label: "Other",         value: "other"         },
    ],
  },
];

  // ── STYLES ────────────────────────────────────────────────
  // All CSS is injected here so you don't need a separate file.
  const CSS = `
    #ar-fab {
      position: fixed; bottom: 28px; right: 28px; z-index: 9999;
      width: 56px; height: 56px; border-radius: 50%;
      background: #C9A96E; border: none; cursor: pointer;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      font-size: 22px; color: #1a1612;
      display: flex; align-items: center; justify-content: center;
      transition: transform 0.2s;
    }
    #ar-fab:hover { transform: scale(1.08); }

    #ar-box {
      position: fixed; bottom: 96px; right: 28px; z-index: 9998;
      width: 360px;
      background: #1a1612; border-radius: 16px;
      border: 1px solid rgba(201,169,110,0.25);
      display: flex; flex-direction: column;
      box-shadow: 0 8px 32px rgba(0,0,0,0.5);
      font-family: Georgia, serif;
      transform: scale(0.9) translateY(12px);
      opacity: 0; pointer-events: none;
      transition: opacity 0.25s, transform 0.25s;
    }
    #ar-box.open { opacity: 1; transform: scale(1) translateY(0); pointer-events: all; }

    #ar-header {
      background: #201c17; border-bottom: 1px solid rgba(201,169,110,0.2);
      padding: 14px 18px; display: flex; align-items: center; gap: 10px;
      border-radius: 16px 16px 0 0;
    }
    #ar-header .av {
      width: 34px; height: 34px; border-radius: 50%;
      background: #C9A96E; display: flex; align-items: center; justify-content: center;
      font-size: 14px; color: #1a1612; flex-shrink: 0;
    }
    #ar-header .title { font-size: 14px; color: #C9A96E; letter-spacing: 0.04em; }
    #ar-header .sub   { font-size: 11px; color: #8a8070; font-family: sans-serif; margin-top: 1px; }

    #ar-msgs {
      flex: 1; overflow-y: auto; padding: 14px;
      display: flex; flex-direction: column; gap: 10px;
    }
    #ar-msgs::-webkit-scrollbar { width: 3px; }
    #ar-msgs::-webkit-scrollbar-thumb { background: #332e28; border-radius: 2px; }

    .ar-msg { display: flex; gap: 7px; align-items: flex-end; }
    .ar-msg.bot  { flex-direction: row; }
    .ar-msg.user { flex-direction: row-reverse; }
    .ar-msg .av {
      width: 26px; height: 26px; min-width: 26px; border-radius: 50%;
      background: #C9A96E; display: flex; align-items: center; justify-content: center;
      font-size: 11px; color: #1a1612;
    }
    .ar-bubble {
      max-width: 82%; padding: 9px 13px; font-size: 12.5px; line-height: 1.55;
    }
    .bot .ar-bubble {
      background: #2a2420; color: #f0ebe3; border-radius: 12px 12px 12px 3px;
    }
    .user .ar-bubble {
      background: #C9A96E; color: #1a1612; border-radius: 12px 12px 3px 12px;
      font-family: sans-serif; font-size: 12px;
    }

    #ar-opts {
      padding: 10px 14px; display: flex; flex-wrap: nowrap; gap: 7px;
      border-top: 1px solid rgba(201,169,110,0.15);
      background: #221e19; border-radius: 0 0 16px 16px; min-height: 58px;
    }
    .ar-opt {
      background: transparent; border: 1px solid rgba(201,169,110,0.3);
      color: #E8D5B0; padding: 6px 13px; border-radius: 18px;
      font-size: 11.5px; font-family: sans-serif; cursor: pointer;
      transition: all 0.18s;
    }
    .ar-opt:hover { background: #C9A96E; color: #1a1612; border-color: #C9A96E; }

    .ar-card {
      background: #2a2420; border: 1px solid rgba(201,169,110,0.2);
      border-radius: 9px; padding: 11px 13px; margin: 3px 0;
      font-family: sans-serif;
    }
    .ar-card .cn { font-size: 13px; color: #C9A96E; font-weight: 600; font-family: Georgia; margin-bottom: 4px; }
    .ar-card .cn small { font-weight: normal; color: #9a8a70; font-size: 11px; margin-left: 5px; }
    .ar-card .cn2 { font-size: 11px; color: #8a8070; line-height: 1.5; }
    .ar-card .match { font-size: 11px; color: #7dbb6e; margin-top: 6px; }

    .ar-dots { display: flex; gap: 4px; align-items: center; padding: 3px 0; }
    .ar-dots span {
      width: 5px; height: 5px; background: #C9A96E; border-radius: 50%;
      animation: arBounce 1s infinite; opacity: 0.6;
    }
    .ar-dots span:nth-child(2) { animation-delay: 0.15s; }
    .ar-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes arBounce {
      0%,100% { transform: translateY(0); }
      50%      { transform: translateY(-5px); }
    }

    .ar-restart {
      background: none; border: none; color: #6a6050;
      font-size: 11px; font-family: sans-serif;
      cursor: pointer; text-decoration: underline; padding: 4px;
    }
    .ar-restart:hover { color: #C9A96E; }

    @media (max-width: 420px) {
      #ar-box { width: calc(100vw - 24px); right: 12px; }
    }
  `;

  // ── STATE ──────────────────────────────────────────────────
  let step = 0;
  let answers = {};
  let isOpen = false;

  // ── BUILD DOM ─────────────────────────────────────────────
  function init() {
    // Inject CSS
    const style = document.createElement('style');
    style.textContent = CSS;
    document.head.appendChild(style);

    // FAB button (the little circle in the corner)
    const fab = document.createElement('button');
    fab.id = 'ar-fab';
    fab.title = 'Scent Guide';
    fab.innerHTML = '✦';
    fab.addEventListener('click', toggleChat);
    document.body.appendChild(fab);

    // Chat box
    const box = document.createElement('div');
    box.id = 'ar-box';
    box.innerHTML = `
      <div id="ar-header">
        <div class="av">✦</div>
        <div>
          <div class="title">Aromea Scent Guide</div>
          <div class="sub">AI-powered fragrance consultant</div>
        </div>
      </div>
      <div id="ar-msgs"></div>
      <div id="ar-opts"></div>
    `;
    document.body.appendChild(box);

    startChat();
  }

  // ── TOGGLE OPEN/CLOSE ────────────────────────────────────
  function toggleChat() {
    isOpen = !isOpen;
    document.getElementById('ar-box').classList.toggle('open', isOpen);
  }

  // ── HELPERS ───────────────────────────────────────────────
  const msgs = () => document.getElementById('ar-msgs');
  const opts = () => document.getElementById('ar-opts');

  function scrollBottom() {
    const m = msgs();
    m.scrollTop = m.scrollHeight;
  }

  // Add a message bubble to the chat
  function addMsg(type, html) {
    const div = document.createElement('div');
    div.className = `ar-msg ${type}`;
    if (type === 'bot') {
      div.innerHTML = `<div class="av">✦</div><div class="ar-bubble">${html}</div>`;
    } else {
      div.innerHTML = `<div class="ar-bubble">${html}</div>`;
    }
    msgs().appendChild(div);
    scrollBottom();
    return div;
  }

  // Show the animated typing dots
  function showTyping() {
    const div = document.createElement('div');
    div.className = 'ar-msg bot';
    div.id = 'ar-typing';
    div.innerHTML = `<div class="av">✦</div><div class="ar-bubble"><div class="ar-dots"><span></span><span></span><span></span></div></div>`;
    msgs().appendChild(div);
    scrollBottom();
  }

  function removeTyping() {
    const t = document.getElementById('ar-typing');
    if (t) t.remove();
  }

  // Replace the option buttons at the bottom
  function setOptions(optList) {
    const o = opts();
    o.innerHTML = '';
    optList.forEach(opt => {
      const btn = document.createElement('button');
      btn.className = 'ar-opt';
      btn.textContent = opt.label;
      btn.addEventListener('click', () => handleAnswer(opt));
      o.appendChild(btn);
    });
  }

  // ── FLOW ──────────────────────────────────────────────────

  function startChat() {
    step = 0;
    answers = {};
    msgs().innerHTML = '';
    opts().innerHTML = '';

    showTyping();
    setTimeout(() => {
      removeTyping();
      const q = QUESTIONS[0];
      addMsg('bot', q.text.replace(/\n/g, '<br>'));
      setOptions(q.options);
    }, 800);
  }

  function handleAnswer(option) {
    const q = QUESTIONS[step];
    answers[q.key] = option.value;          // save the answer
    addMsg('user', option.label);           // show what the user picked
    opts().innerHTML = '';                  // clear the buttons
    step++;

    if (step < QUESTIONS.length) {
      // More questions to ask
      showTyping();
      setTimeout(() => {
        removeTyping();
        const next = QUESTIONS[step];
        addMsg('bot', next.text.replace(/\n/g, '<br>'));
        setOptions(next.options);
      }, 600);
    } else {
      // All questions answered — call the AI
      showTyping();
      setTimeout(() => {
        removeTyping();
        addMsg('bot', 'Consulting our collection for you...');
        showTyping();
        callAPI(answers);
      }, 600);
    }
  }

  // ── API CALL ──────────────────────────────────────────────
  // This calls your chatbot.php file.
  // The path below assumes chatbot.php is at aromea/api/chatbot.php
  // If your BASE_URL is different, adjust accordingly.

  function callAPI(answers) {
    // ⚠️ CHANGE THIS PATH if your project is not at /aromea/
    const apiUrl = '/aromea/api/chatbot.php';

    fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ answers }),
    })
      .then(res => res.json())
      .then(data => {
        removeTyping();
        if (data.error) {
          addMsg('bot', 'Sorry, something went wrong. Please try again.');
          console.error('Chatbot error:', data.error);
          showRestartBtn();
          return;
        }
        showRecommendations(data.recommendations);
      })
      .catch(err => {
        removeTyping();
        addMsg('bot', 'Could not reach the server. Please try again.');
        console.error(err);
        showRestartBtn();
      });
  }

  // ── SHOW RESULTS ──────────────────────────────────────────
  function showRecommendations(recs) {
    if (!recs || recs.length === 0) {
      addMsg('bot', "We couldn't find a perfect match right now. Try adjusting one of your preferences.");
      showRestartBtn();
      return;
    }

    addMsg('bot', `Here are ${recs.length} fragrances curated just for you:`);

    recs.forEach((p, i) => {
      const div = document.createElement('div');
      div.className = 'ar-msg bot';

      // Build a link to the fragrance detail page if you have one
      // Change the href to match your actual fragrance detail URL
      const link = `/aromea/fragrance-details.php?id=${p.id}`;

      div.innerHTML = `
        <div class="av" style="opacity:0">✦</div>
        <div class="ar-card">
          <div class="cn">
            ${i + 1}. ${p.name}
            <small>${p.brand || ''} — ${p.price ? p.price.toFixed(2) + ' DZD' : ''}</small>
          </div>
          <div class="cn2">${p.reason}</div>
          <div class="match">✓ ${p.match_score}% match</div>
        </div>`;
      msgs().appendChild(div);
    });

    scrollBottom();

    setTimeout(() => {
      addMsg('bot', 'Would you like to explore one of these, or start over?');
      opts().innerHTML = '';

      // Create a button per recommendation linking to its detail page
      recs.forEach(p => {
        const btn = document.createElement('button');
        btn.className = 'ar-opt';
        btn.textContent = `View ${p.name}`;
        btn.addEventListener('click', () => {
          window.location.href = `/aromea/fragrance-details.php?id=${p.id}`;
        });
        opts().appendChild(btn);
      });

      showRestartBtn();
    }, 400);
  }

  function showRestartBtn() {
    const btn = document.createElement('button');
    btn.className = 'ar-restart';
    btn.textContent = 'Start over';
    btn.addEventListener('click', startChat);
    opts().appendChild(btn);
  }

  // ── KICK OFF ──────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
