import sqlite3
import json

db_clientes = "db/studiolivecode_clientes.db"
db_venc = "db/studiolivecode_vencimento.db"

try:
    conn_c = sqlite3.connect(db_clientes)
    cur_c = conn_c.cursor()
    cur_c.execute("SELECT id, mac_address, title, expire_date, id_user FROM ibo")
    ibo_rows = cur_c.fetchall()
    print("=== IBO ===")
    for r in ibo_rows:
        print(r)
except Exception as e:
    print("Error IBO:", e)

try:
    conn_v = sqlite3.connect(db_venc)
    cur_v = conn_v.cursor()
    cur_v.execute("SELECT id, vencimento FROM vencimento")
    v_rows = cur_v.fetchall()
    print("=== VENCIMENTO ===")
    for r in v_rows:
        print(r)
except Exception as e:
    print("Error VENC:", e)

