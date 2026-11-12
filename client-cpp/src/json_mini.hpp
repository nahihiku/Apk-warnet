#pragma once
#include <string>
#include <vector>
#include <map>
#include <cctype>
#include <cstdlib>
#include <sstream>

// Lightweight native C++ JSON parser & builder (zero external dependencies)
struct JsonValue {
    enum Type { NUL, BOOL_T, NUMBER, STRING, ARRAY, OBJECT } type = NUL;
    bool bool_val = false;
    double num_val = 0.0;
    std::string str_val;
    std::vector<JsonValue> arr_val;
    std::map<std::string, JsonValue> obj_val;

    bool has(const std::string& key) const {
        return type == OBJECT && obj_val.find(key) != obj_val.end();
    }

    const JsonValue& operator[](const std::string& key) const {
        static JsonValue null_val;
        if (type != OBJECT) return null_val;
        auto it = obj_val.find(key);
        return (it != obj_val.end()) ? it->second : null_val;
    }

    std::string as_str(const std::string& def = "") const {
        if (type == STRING) return str_val;
        if (type == NUMBER) {
            long long v = (long long)num_val;
            return std::to_string(v);
        }
        return def;
    }

    long long as_int(long long def = 0) const {
        if (type == NUMBER) return (long long)num_val;
        if (type == STRING && !str_val.empty()) return std::atoll(str_val.c_str());
        if (type == BOOL_T) return bool_val ? 1 : 0;
        return def;
    }

    bool as_bool(bool def = false) const {
        if (type == BOOL_T) return bool_val;
        if (type == NUMBER) return num_val != 0;
        if (type == STRING) return str_val == "true" || str_val == "1";
        return def;
    }
};

class JsonParser {
    const std::string& s;
    size_t pos = 0;

    void skip_ws() {
        while (pos < s.size() && (s[pos] == ' ' || s[pos] == '\t' || s[pos] == '\n' || s[pos] == '\r')) {
            pos++;
        }
    }

    std::string parse_string() {
        std::string out;
        if (pos < s.size() && s[pos] == '"') pos++;
        while (pos < s.size() && s[pos] != '"') {
            if (s[pos] == '\\' && pos + 1 < s.size()) {
                pos++;
                char c = s[pos];
                if (c == 'n') out += '\n';
                else if (c == 'r') out += '\r';
                else if (c == 't') out += '\t';
                else if (c == '"' || c == '\\' || c == '/') out += c;
                else if (c == 'u' && pos + 4 < s.size()) {
                    pos += 4;
                } else {
                    out += c;
                }
            } else {
                out += s[pos];
            }
            pos++;
        }
        if (pos < s.size() && s[pos] == '"') pos++;
        return out;
    }

    JsonValue parse_value() {
        skip_ws();
        JsonValue v;
        if (pos >= s.size()) return v;

        char c = s[pos];
        if (c == '"') {
            v.type = JsonValue::STRING;
            v.str_val = parse_string();
        } else if (c == '{') {
            v.type = JsonValue::OBJECT;
            pos++;
            skip_ws();
            if (pos < s.size() && s[pos] == '}') {
                pos++;
                return v;
            }
            while (pos < s.size()) {
                skip_ws();
                if (pos < s.size() && s[pos] == '}') { pos++; break; }
                std::string key = parse_string();
                skip_ws();
                if (pos < s.size() && s[pos] == ':') pos++;
                JsonValue val = parse_value();
                v.obj_val[key] = val;
                skip_ws();
                if (pos < s.size() && s[pos] == ',') pos++;
                else if (pos < s.size() && s[pos] == '}') { pos++; break; }
                else break;
            }
        } else if (c == '[') {
            v.type = JsonValue::ARRAY;
            pos++;
            skip_ws();
            if (pos < s.size() && s[pos] == ']') {
                pos++;
                return v;
            }
            while (pos < s.size()) {
                skip_ws();
                if (pos < s.size() && s[pos] == ']') { pos++; break; }
                v.arr_val.push_back(parse_value());
                skip_ws();
                if (pos < s.size() && s[pos] == ',') pos++;
                else if (pos < s.size() && s[pos] == ']') { pos++; break; }
                else break;
            }
        } else if (c == 't' && s.compare(pos, 4, "true") == 0) {
            v.type = JsonValue::BOOL_T;
            v.bool_val = true;
            pos += 4;
        } else if (c == 'f' && s.compare(pos, 5, "false") == 0) {
            v.type = JsonValue::BOOL_T;
            v.bool_val = false;
            pos += 5;
        } else if (c == 'n' && s.compare(pos, 4, "null") == 0) {
            v.type = JsonValue::NUL;
            pos += 4;
        } else if (c == '-' || std::isdigit((unsigned char)c)) {
            v.type = JsonValue::NUMBER;
            size_t start = pos;
            if (s[pos] == '-') pos++;
            while (pos < s.size() && (std::isdigit((unsigned char)s[pos]) || s[pos] == '.' || s[pos] == 'e' || s[pos] == 'E' || s[pos] == '+' || s[pos] == '-')) {
                pos++;
            }
            v.num_val = std::atof(s.substr(start, pos - start).c_str());
        } else {
            pos++;
        }
        return v;
    }

public:
    JsonParser(const std::string& json) : s(json), pos(0) {}
    static JsonValue parse(const std::string& json) {
        JsonParser p(json);
        return p.parse_value();
    }
};

inline std::string json_escape(const std::string& in) {
    std::string out;
    for (char c : in) {
        if (c == '"') out += "\\\"";
        else if (c == '\\') out += "\\\\";
        else if (c == '\n') out += "\\n";
        else if (c == '\r') out += "\\r";
        else out += c;
    }
    return out;
}
