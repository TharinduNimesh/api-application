db.auth('admin', 'password')

db = db.getSiblingDB('api_db')

db.createUser({
    user: 'admin',
    pwd: 'password',
    roles: [
        {
            role: 'readWrite',
            db: 'api_db'
        }
    ]
})
